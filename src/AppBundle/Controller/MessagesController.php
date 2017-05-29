<?php
/**************************************************************
 * This Library has been created by :
 *      Vicent Nos @nOsDelAbismo
 *      Andersson Gabriel @ander7agar
 *      Sheila Mundo @sheilapimpampum
 **************************************************************/
namespace AppBundle\Controller;

use AppBundle\Entity\inputWords;
use AppBundle\Entity\groups;
use AppBundle\Controller\TrantorCoreController;
use AppBundle\Entity\weightWords;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Creativechain\Core\Creativecoin;
use AppBundle\Creativechain\Core;

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

    public function saveAction(Request $request){
        $ref=$request->get('ref');
        $word=$request->get('input');
        $client = new TrantorCoreController();
        $client->setContainer($this->container);
        $em = $this->getDoctrine()->getManager();
        $Repo = $em->getRepository("AppBundle:inputWords");
        $consulta = $Repo->findOneBy(array('inWords' => $word, 'outFWords'=>$ref));
        if($word != '' && $ref != '') {
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
        }else{
            $results = 'Params invalids.';
        }
        $response = new Response(json_encode(array('results' => $results)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function searchAction(Request $request){
        $client = new TrantorCoreController();
        $client->setContainer($this->container);

        $send = new MessagesController();
        $send->setContainer($this->container);

        $text=$request->get('text');

        $em = $this->getDoctrine()->getManager();
        $Repo = $em->getRepository("AppBundle:inputWords");
        $consulta = $Repo->findOneBy(array('inWords' => $text));
        $textfiltrat = $client->filtrar($text);
        if(count($textfiltrat)>1) {
            foreach ($textfiltrat as $paraulafiltra) {
                $this->weigth($paraulafiltra, $textfiltrat);
            }
        }else{
            $this->weigth($textfiltrat, $textfiltrat);
        }

        $torna = $client->iniciarconversacion($text);

        $creativecoin = new Creativecoin();
        $results = Array();
        if (!empty($torna)) {
            foreach ($torna as $una){
                array_push($results,$creativecoin->getDataFromReference($una));
            }
        }else{
            if($consulta){
                array_push($results,$creativecoin->getDataFromReference($consulta->getOutFWords()));
            }else{
                $results = 'No Results';
            }
        }
        $response = new Response(json_encode(array('results' => $results)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
