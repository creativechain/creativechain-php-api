<?php
/**************************************************************
 * This Library has been created by :
 *      Vicent Nos @nOsDelAbismo
 *      Andersson Gabriel @ander7agar
 *      Sheila Mundo @sheilapimpampum
 **************************************************************/
namespace AppBundle\Controller;

use AppBundle\Entity\amount;
use AppBundle\Entity\inputWords;
use AppBundle\Entity\groups;
use AppBundle\Controller\TrantorCoreController;
use AppBundle\Entity\weightWords;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Creativechain\Core\Creativecoin;
use AppBundle\Creativechain\Core\RPCClient;
use AppBundle\Creativechain\Core;
use Symfony\Component\HttpFoundation\Session\Session;


class MessagesController extends Controller
{
    function weigth($paraulafiltra,$text){
        $client = new TrantorCoreController();
        $client->setContainer($this->container);
        $em = $this->getDoctrine()->getManager();
        $Repo = $em->getRepository("AppBundle:weightWords");
        $consulta = $Repo->findOneBy(array('word' => $paraulafiltra));
        if (!$consulta) {
            $tupla = $client->puntuar($text);
            $peso = $client->peso($text, $paraulafiltra, $tupla);
            $weight = new weightWords();
            $weight->setWord($paraulafiltra);
            $weight->setWeight($peso);
            $em->persist($weight);
            $em->flush();
        }
    }
    public function checkCredentials(){
        $session = new Session();
        $port = $session->get('port');
        $user = $session->get('user');
        $password = $session->get('password');
        $ip = $session->get('ip');
        $result = "";
        if($port && $user && $password && $ip){
            $result = "ok";
        }
        return $result;
    }
    public function saveDataDirectlyAction(Request $request)
    {
        $results="";
        if ($this->checkCredentials() == 'ok') {
            $dataRquest = $request->get('data');
            $data = json_decode($dataRquest);
            if ($data->title) {
                $datos=json_encode($data);
                var_dump($datos);
                $creativecoin = new Creativecoin();

                $datosT = $creativecoin->storeData($datos);
                $transactions = json_encode($datosT);
                $datosI = $creativecoin->storeData($transactions);
                $ref = $datosI['ref'];

                $index = json_encode($datosI);
                $results = json_decode($datos);
                    if (!empty($data)) {
                        var_dump($datosI);
                        var_dump($datosT);
                        if (strlen($datosI['ref']) > 2 and strlen($datosT['ref']) > 2) {
                            $results = $this->indexIn($ref, $results->title);
                        }
                    } else {
                        $results = "missing data";
                    }
            }
        }else {
            $results = "Credentials not configured";
        }
        $response = new Response(json_encode(array('results' => $results)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    public function CredentialsAction(Request $request)
    {
        $session = new Session();
        $session->clear();
        $session->start();

        $ip=$request->get('ip');
        $port=$request->get('port');
        $user=$request->get('user');
        $password=$request->get('password');

        // set and get session attributes
        $session->set('ip', $ip);
        $session->set('port', $port);
        $session->set('user', $user);
        $session->set('password', $password);

        $response = new Response(json_encode(array('results' => 'Ok')));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    public function generatePayAddressAction(Request $request){
        if($this->checkCredentials() == 'ok'){

            $json = $request->get('data');
            $addressPay = new Creativecoin();
            $results = $addressPay->getAddressPay($json);
            if(!$results['error']){
                $addessNew = $results['address'];
                $price = $results['price'];
                $em = $this->getDoctrine()->getManager();
                $amount = new amount();
                $amount->setAddress($addessNew);
                $amount->setData($json);
                $amount->setAmount($price);
                $em->persist($amount);
                $em->flush();
                $results = json_encode(array('address' => $addessNew, 'amount' => $price));
            }
        }else{
            $results = "Credentials not configured";
        }
        $response = new Response(json_encode(array('results' => $results)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    public function validatePayAction(Request $request){
        $torna = "";
        if($this->checkCredentials() == 'ok'){
            $call = new RPCClient();

            $address = $request->get('address');
            if($address) {
                $em = $this->getDoctrine()->getManager();
                $Repo = $em->getRepository("AppBundle:amount");
                $consulta = $Repo->findOneBy(array('address' => $address));
                if ($consulta) {
                    $amount = $consulta->getAmount();
                    $datos = $consulta->getData();

                    $balance = $call->getReceivedByAddress($address);

                    if ($amount >= $balance) {
                        $datosdec = json_decode($datos);
                        $data_len = intval(ceil(strlen($datos) / 1000));

                        $fee_price = 20000;
                        $amount = $data_len * $fee_price;

                        $creativecoin = new Creativecoin();
                        if ($amount > 0) {
                            if ($balance == false) {
                                $balance = 0;
                            }
                            if ($balance >= floatval($amount)) {
                                $datosT = $creativecoin->storeData($datos);
                                $transactions = json_encode($datosT);
                                $datosI = $creativecoin->storeData($datos);
                                $ref = $datosI['ref'];

                                $index = json_encode($datosI);
                                $data = json_decode($datos);

                                if (!empty($datos)) {
                                    if (strlen($datosI['ref']) > 2 and strlen($datosT['ref']) > 2) {
                                        $this->indexIn($ref, $data->title);
                                        $em->remove($consulta);
                                        $em->persist($consulta);
                                        $em->flush();
                                    }
                                }
                                $torna = json_encode(array('payment' => 'ok', 'CREA' => floatval($balance) / 1e8, 'price' => floatval($amount) / 1e8, 'transactions' => $transactions, 'ref' => $index, 'data' => $datos));
                                session_destroy();
                            }
                            if ($balance < floatval($amount)) {
                                $torna = json_encode(array('payment' => 'wait', 'CREA' => floatval($balance) / 1e8, 'price' => floatval($amount) / 1e8));
                            }
                        } else {
                            $torna = json_encode(array('payment' => 'wait', 'CREA' => floatval($balance)));
                        }
                    } else {
                        $torna = "The amount it's incomplete";
                    }
                } else {
                    $torna = "This Address isn't registered";
                }
            }else {
                $torna = "Parameter address is required";
            }
        }else{
            $torna = "Credentials not configured";
        }
        $response = new Response(json_encode(array('results' => $torna)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    public function indexAction(Request $request){
        if($this->checkCredentials() == 'ok') {
            $ref = $request->get('ref');
            $word = $request->get('input');
            $results = $this->indexIn($ref, $word);
        }else{
            $results = "Credentials not configured";
        }
        $response = new Response(json_encode(array('results' => $results)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    public function indexIn($ref, $word){
        echo $word;
        echo $ref;
        $client = new TrantorCoreController();
        $client->setContainer($this->container);
        $em = $this->getDoctrine()->getManager();
        $Repo = $em->getRepository("AppBundle:inputWords");
        $consulta = $Repo->findOneBy(array('inWords' => $word, 'outFWords' => $ref));
        if ($word != '' && $ref != '') {
            if (!$consulta) {
                $input = new inputWords();
                $input->setOutWords($ref);
                $input->setInWords($word);
                $filtrarIn = $client->filtrar($word);
                $input->setHashWords(md5($filtrarIn . $ref));
                $input->setInFWords($filtrarIn);
                $input->setOutFWords($ref);
                $input->setPermiso("yes");
                $em->persist($input);
                $em->flush();
                $results = 'OK';
            } else {
                $results = 'This word and ref is already save.';
            }
        } else {
            $results = 'Params invalids.';
        }

        return $results;
    }

    public function searchAction(Request $request){
        if($this->checkCredentials() == 'ok') {

            $client = new TrantorCoreController();
            $client->setContainer($this->container);

            $send = new MessagesController();
            $send->setContainer($this->container);

            $text = $request->get('text');
            if($text){
                $em = $this->getDoctrine()->getManager();
                $Repo = $em->getRepository("AppBundle:inputWords");
                $consulta = $Repo->findOneBy(array('inWords' => $text));
                $textfiltrat = $client->filtrar($text);
                if (count($textfiltrat) > 1) {
                    foreach ($textfiltrat as $paraulafiltra) {
                        $this->weigth($paraulafiltra, $textfiltrat);
                    }
                } else {
                    $this->weigth($textfiltrat, $textfiltrat);
                }

                $torna = $client->iniciarconversacion($text);

                $creativecoin = new Creativecoin();
                $results = Array();
                if (!empty($torna)) {
                    foreach ($torna as $una) {
                        array_push($results, $creativecoin->getDataFromReference($una));
                    }
                } else {
                    if ($consulta) {
                        array_push($results, $creativecoin->getDataFromReference($consulta->getOutFWords()));
                    } else {
                        $results = 'No Results';
                    }
                }
            }else{
                $results = "Require param text";
            }
        }else{
            $results = "Credentials not configured";
        }
        $response = new Response(json_encode(array('results' => $results)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
