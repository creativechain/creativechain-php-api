<?php
/**************************************************************
 * This Library has been created by :
 *      Vicent Nos @nOsDelAbismo
 *      Andersson Gabriel @ander7agar
 *      Sheila Mundo @sheilapimpampum
 **************************************************************/
namespace AppBundle\Controller;

use AppBundle\Entity\groups;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\weightWords;
use AppBundle\Entity\inputWords;
use AppBundle\Entity\output;
use \DateTime;


class TrantorCoreController extends Controller
{
    public function orderMultiDimensionalArray($toOrderArray, $field, $inverse = false) {
        $position = array();
        $newRow = array();
        foreach ($toOrderArray as $key => $row) {
            $position[$key] = $row[$field];
            $newRow[$key] = $row;
        }
        if ($inverse) {
            arsort($position);
        } else {
            asort($position);
        }
        $returnArray = array();
        foreach ($position as $key => $pos) {
            $returnArray[] = $newRow[$key];
        }
        return $returnArray;
    }

    // Elimina todos los caracteres extraños
    public function filtrar($cadena) {
        $traduccion = array(
            '?' => '', '\'' => '', '¿' => '', ',' => ' ', ';' => '', '¡' => '', '!' => '', '_' => '', '.' => ' ', '{' => '',
            '}' => '', '¨' => '', '+' => '', '-' => '', '"' => '', 'ª' => '', '|' => '', '·' => '', '$' => '',
            '#' => '', '~' => '', '%' => '', '€' => '', '¬' => '', '&' => '', '/' => '', '(' => '', ')' => '',
            '=' => '', '*' => '', '^' => '', '`' => '', '[' => '', ']' => '', 'À' => 'A', 'Á' => 'A', 'Â' => 'A',
            'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o',
            'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'è' => 'e', 'é' => 'e',
            'ê' => 'e', 'ë' => 'e', 'Ç' => 'c', 'ç' => 'c', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'ì' => 'i',
            'í' => 'i', 'î' => 'i', 'ï' => 'i', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'ù' => 'u', 'ú' => 'u',
            'û' => 'u', 'ü' => 'u', 'ÿ' => 'y', 'Ñ' => 'N', 'ñ' => 'n', "\\" => "", '`' => '', '´' => '', '<' => '', '>' => '');

        $cadena = strtr($cadena, $traduccion);
        $cadena = preg_replace('/\s+/', ' ', $cadena);
        $cadena = strtolower($cadena);
        $cadena = trim($cadena);

        return $cadena;
    }

    //Puntua las palabras según su importancia
    public function puntuar($entrada) {
        //$this->Mysql = ClassRegistry::init('User');

        // Extraigo als palabras
        $palabras = explode(" ", $entrada);

        // Asigno el numero de filas que tiene esa palabra en su tabla
        $tuplas="";
        foreach ($palabras as $clave => $valor) {

            // TODO: ¡ESTO ES UNA PRUEBA PARA LIMPIAR LA ENTRADA, YA QUE ERAMOS VULNERABLES A INYECCIONES!
            $valor = addslashes($valor);

            $em=$this->getDoctrine()->getManager();
            $wordsRepo = $em->getRepository("AppBundle:inputWords");
            $consultWord = $wordsRepo->findBy(array('inFWords'=>$valor));
            // Extraigo el numero de filas
            //$consultWord = $this->Mysql->query('SELECT COUNT(1) AS cantidad FROM ' . $bot_type . '_textoEntrada WHERE MATCH(entradaF) AGAINST(\''.$valor.'\')');

            //A signo el numero de filas en tabla
            if ($consultWord) {
                //list($puntuacion) = $consulta[0];
                $tuplas[$clave] = count($consultWord);
                // En caso de no existir la tabla, el numero de tuplas es 0
            }else
                $tuplas[$clave] = 0;
        }
        // Devuelvo la puntuacion de todas las palabras
        return $tuplas;
    }

    //Devuelve la palabra cuyas entradas sean las menos posibles, mientras no sea 0
    public function sujetode($entrada, $tuplas) {
        $sujeto = "";
        // Extraemos las palabras
        $entrada = $this->filtrar($entrada);
        $palabras = explode(" ", $entrada);

        // Numero de tuplas muy alto, para que haya algun valor por debajo y elegirlo
        $min = 1000000000;

        // Recorre las palabras en busca de la palabra con menor numero de tuplas, pero que no sea 0
        foreach ($palabras as $clave => $valor){
            if (isset($tuplas[$clave]))
            {
                if ($tuplas[$clave] <= $min and $tuplas[$clave] != 0) {
                    $sujeto = $valor;
                    $min = $tuplas[$clave];
                }
            }
        }
        // Devuelve la palabra con menos tuplas
        //print_r(" </br> tuplas dins funcio sujeto de : ");
        //var_dump($tuplas);
        //print_r("</br>");
        return $sujeto;
    }

    // Obtiene el valor que tiene una palabra dentro de una oracion
    public function peso($entrada, $palabra, $tupla) {
        // Comprobamos si la palabra tiene tuplas
        if ($tupla > 0) {
            // El peso es la inversa de las tuplas, ya que a mas tuplas de la tabla, menos valor tiene la palabra
            $peso = 1 / (int)$tupla;
            // Numero de apariciones de la palabra en la entrada de la base de datos
            $apariciones = 0;
            // Cuenta el numero de apariciones de la palabra
            //print_r($entrada." ".$palabra);
            if($entrada != $palabra){
                foreach ($entrada as $key => $val) {
                    if ($palabra == $val) {
                        ++$apariciones;
                    }
                }
            }else{
                ++$apariciones;
            }

            // El peso se calcula, por el peso base multiplicado por el numero de apariciones
            // de la palabra en la entrada obtenida de la bd
            //print_r("Peso ".$peso * $apariciones);
            return $peso * $apariciones;

            // Si no tiene puntos independientemente de las apariciones, el resultado sera 0
        }else
            return 0;
    }

    public function sujetodeA($entrada, $tuplas) {
        // Extraemos las palabras
        $palabras = explode(" ", $entrada);

        // Numero de tuplas muy alto, para que haya algun valor por debajo y elegirlo
        $min = 1000000000;

        // Recorre las palabras en busca de la palabra con menor numero de tuplas, pero que no sea 0
        $sujeto='';
        foreach ($palabras as $clave => $valor)
            if ($tuplas[$clave] <= $min) {
                $sujeto = $valor;
                $min = $tuplas[$clave];
            }

        // Devuelve la palabra con menos tuplas
        return $sujeto;
    }

    // Busca la respuesta a la entrada proporcionada por el usuario, esta debe estar ya ¡filtrada!
    public function consultar($entrada) {
        //$this->Mysql = ClassRegistry::init('User');

        $limit=500;
        $resultado = array();
        //Filtrando la entrada
        $entradaF = $this->filtrar($entrada);
        //Extrae las palabras de la entrada
        $palabrasEntradaF = explode(' ', $entradaF);
        //Numero de palabras que forman la entrada
        $numPalsEntrada = count($palabrasEntradaF);
        //Calculamos el numero de sujetos
        $numsujetos = round($numPalsEntrada*0.4);
        if ($numsujetos<1){ $numsujetos=1; }
        if ($numsujetos>5){ $numsujetos=5; }
        //echo "numero de sujetos ".$numsujetos."</br>";
        $sujetos=array();

        $aux = $entradaF;
        $aux=preg_replace("/[[:punct:]]+/i", " ", $aux);
        $aux = trim($aux);
        $aux = preg_replace("/[[:space:]]+/i", " ", $aux);
        //print_r(" aux ".$aux." </br>");

        for($i=0;$i<$numsujetos;$i++){
            //Puntua las palabras
            $tuplas = $this->puntuar($aux);
            //print_r("tuplas resultat funcio puntuar: ");
            //var_dump($tuplas);
            //Sujeto de la entrada
            $sujeto = $this->sujetode($aux, $tuplas);
            //print_r("NUMERO PALABRAS ENTRADA ".$sujeto."</br>");

            $aux = preg_replace('/([[:space:]]|^){1}'.$sujeto.'([[:space:]]|$){1}/i', " ", $aux);
            $aux = rtrim($aux);
            $aux = preg_replace("/[[:space:]]+/i"," ",$aux);
            array_push($sujetos, $sujeto);

        }

        $tuplas = $this->puntuar($entradaF);
        //Vamos a calcular el maximo valor de los sujetos y a igualarlos
        $puntuacionsuj = $this->puntuar(implode(" ", $sujetos));
        $maxpunt = max($puntuacionsuj);
        foreach ($palabrasEntradaF as $key => $value){
            if (in_array($value,$sujetos)){
                $tuplas[$key]=$maxpunt;
            }
        }
        $sujetoscp = $sujetos;

        foreach($sujetos as $sujeto){

            // Extrae todas los datos almacenadas en la tabla sujeto, que contengan una de las palabras de la entrada actual y ademas le añado la session y tal
            if ($sujeto != ""){
                //print_r("hem aplegat dins a vore consulta : ");
                $em=$this->getDoctrine()->getManager();
                $Repo = $em->getRepository("AppBundle:inputWords");
                $consulta = $Repo->findBy(array('inFWords'=>$sujeto));
                //$consulta = $this->Mysql->query('SELECT entradaF, salidaF, salida, hash FROM ' . $bot_type . '_textoEntrada WHERE entradaF = \'' . $sujeto . '\' LIMIT '.$limit);

                if($consulta){
                    //var_dump($consulta);
                    foreach ($consulta as $row) {
                        //print_r("la row: ");
                        //var_dump($row->getOutWords());
                        //print_r("la row F: ");
                        //var_dump($row->getInFWords());

                        //$row = $row[$bot_type . '_textoEntrada'];

                        //Obtiene las palabras filtradas de la entrada obtenida de la base de datos
                        $palabrasEntradaBD = explode(' ', $this->filtrar($row->getInFWords()));

                        //Se obtiene el valor de las palabras teniendo en cuenta el numero
                        //de lineas de su tabla y su numero de apariciones
                        $peso = 0;
                        $encontradas = 0;
                        //$pesoV = "";
                        $pesosobrante = 0;
                        foreach ($palabrasEntradaF as $key => $val) {
                            //Se busca la palabra y el peso que tiene en la oracion
                            //print_r("el aux : ");

                            $aux = $this->peso($palabrasEntradaBD, $val, $tuplas[$key]);

                            //var_dump($aux);
                            //Se buscan las palabras de la entrada que coincidan con la entrada de la BD
                            //if (eregi($val, $row['entradaF'])) {
                            if (strpos( implode($palabrasEntradaBD),$val)) {
                                $encontradas++;
                                if ($aux > 0) {
                                    $peso += $aux;
                                } else {
                                    $peso += 1 / 5000;
                                }
                            } else {
                                if ($aux > 0) {
                                    $pesosobrante += $aux;
                                } else {
                                    $pesosobrante += 1 / 10000; //Un valor muy pequeño para que penalice algo
                                }
                            }
                        }

                        //Calcular el peso de las palabras en la BD que no estan en la entrada

                        $tuplasBD = $this->puntuar($this->filtrar($row->getInFWords()));

                        //Vamos a calcular el maximo valor de los sujetos y a igualarlos
                        foreach ($palabrasEntradaBD as $key => $value) {
                            if (in_array($value, $sujetos)) {
                                $tuplasBD[$key] = $maxpunt;
                            }
                        }

                        foreach ($palabrasEntradaBD as $key => $val) {
                            $aux = $this->peso($palabrasEntradaBD, $val, $tuplasBD[$key]);

                            //Se buscan las palabras de la entrada de la BD que coincidan con la entrada
                            //Si se encontró la palabra
                            //if (!eregi($val, $entradaF)) {
                            if (!strpos($entradaF,$val)) {
                                if ($aux > 0) {
                                    $pesosobrante += $aux;
                                } else {
                                    if ($aux > 0) {
                                        $pesosobrante += $aux;
                                    } else {
                                        $pesosobrante += 1 / 10000; //Un valor muy pequeño para que penalice algo
                                    }
                                }
                            }
                        }

                        //El peso final es calculado, por el peso de las palabras en la frase mas el porcentaje de palabras encontradas en total
                        //Ej: Buscamos: Hola
                        //    Encontramos: Hola que tal (Peso = 0.02)
                        //    Coincidencia: 1/3
                        //    Peso final: 0.02 + 1/3 = 0.35

                        $propor1 = $encontradas / $numPalsEntrada;
                        //print_r("propor1 ".$propor1."</br>");
                        $propor2 = $numPalsEntrada / count($palabrasEntradaBD);
                        //print_r("propor2 ".$propor2."</br>");
                        $propor3 = 1 / count($palabrasEntradaBD);
                        //print_r("propor3 ".$propor3."</br>");

                        $pesoFinal = $peso + $propor2 + $propor1 - $propor3;
                        //print_r("pesoFinal ".$pesoFinal."</br>");

                        //$pesoFinal = ($peso - $pesosobrante);
                        //print_r("el Peso final ".$pesoFinal."</br>");
                        //Almacenamos el resultado con las prioridades por cada oracion siempre que el pesoFinal
                        //supere un 0.15, ya que frases de menor peso no tienen sentido (posiblemente se deba ajustar)

                        if ($pesoFinal > 0) {
                            //$aux2 = array($pesoFinal, $row['entradaF'], $row['salida'], $row['hash'], "Encontradas: $encontradas Peso: $peso Sobra: $pesosobrante eDB: " . count($palabrasEntradaBD) . " Sujetos: " . count($sujetoscp) . " -> ");
                            $aux2 = array($pesoFinal, $row->getInFWords(), $row->getOutWords(), $row->getHashWords(), "Encontradas: $encontradas Peso: $peso Sobra: $pesosobrante eDB: " . count($palabrasEntradaBD) . " Sujetos: " . count($sujetoscp) . " -> ");

                            foreach ($sujetoscp as $sujetocp) {
                                $aux2[4] .= ($sujetocp == $sujeto) ? "<strong>" . $sujetocp . "</strong> / " : $sujetocp . " / ";
                            }
                            $aux2[4] = rtrim($aux2[4], "/ ");
                            $aux2[4] .= "<br/>Pesos entrada: ";
                            foreach ($palabrasEntradaF as $key => $value) {
                                $aux2[4] .= $value . " -> ";
                                if ($tuplas[$key] == 0)
                                    $aux2[4] .= "0 ";
                                else
                                    $aux2[4] .= (1 / (int)$tuplas[$key]) . " ";
                            }
                            $aux2[4] .= "<br/>Pesos entrada BD: ";
                            foreach ($palabrasEntradaBD as $key => $value) {
                                $aux2[4] .= $value . " -> ";
                                if ($tuplasBD[$key] == 0)
                                    $aux2[4] .= "0 ";
                                else
                                    $aux2[4] .= (1 / (int)$tuplasBD[$key]) . " ";
                            }
                            array_push($resultado, $aux2);
                        }
                    }
                }
            }
            else{
            }
            // TODO: Modificar esta consulta
            // Si falla la busqueda exacta hace un match against
            //if (empty($consulta)){
            //  $consulta = $this->Mysql->query('SELECT entradaF, salidaF, salida, hash FROM ' . $bot_type . '_textoEntrada WHERE MATCH(entradaF) AGAINST(\'' . $sujeto . '\') LIMIT '.$limit);
            //}

            //if ($consulta !== false) {
            //}
        }
        //print_r("resultado: \n");
        //var_dump($resultado);
        return $resultado;
    }

    public function hablar($entrada, $datos, $camid = 0, $idmo = 0, $controltomado = false, $auxid = NULL) {
        $datos['res_previa']= $datos['respuesta'];
        $porcentaje 		= 0;
        $entradasintags 	= $entrada;
        $vars = array();

        // Limpiamos el mensaje de entrada
        $entradaF = $this->filtrar($entrada);

        $salida = "";
        $salidaNueva = Array();
        // Obtengo el listado de posibles salidas
        //var_dump($entradasintags);
        if (empty($salida)) {
            $lista 	= $this->consultar($entradasintags);
            //print_r("</br>".count($lista)." salidaaa </br>");
            $factor = 0.86;
            $posibles = array();
            // Compruebo si se ha encontrado alguna salida aceptable
            if (count($lista) > 0) {
                $lista = $this->orderMultiDimensionalArray($lista, 0, true);

                /*if (count($lista) > 0) {
                    foreach ($lista as $indice => $valor) {
                            if (preg_match($listhola, $valor[2]) != 1)
                            {
                                $salida = $valor[2];
                                break;
                            }

                    }
                }else{*/
                $salida = $lista[0][2];//Le asignamos un valor por si luego no encontrara otro
                //}

                //Valores predeterminados para la busqueda
                $maxpeso = $lista[0][0];

                //Busco las mejores salida
                foreach ($lista as $indice => $valor) {
                    if ($valor[0] >= $maxpeso * $factor) {
                        if (!in_array($valor[3], $datos['hashes'])){
                            array_push($posibles, $valor);
                        }
                    } else {
                        break;
                    }
                }

                if (count($posibles)!=0){
                    $i = array_rand($posibles);
                    array_push($datos['hashes'], $posibles[$i][3]);
                    $salida = $posibles[$i][2];
                    //array_push($salidaNueva,$posibles[$i][2]);
                }
            }
            else {
                // Buscamos la respuesta para la palabra con mayor peso
                $sujrespnose = $this->sujetode($entradasintags, $this->puntuar($entrada));
                $lista = $this->consultar($sujrespnose);

                //Compruebo si se ha encontrado alguna salida aceptable
                if (count($lista) > 0) {
                    $lista = $this->orderMultiDimensionalArray($lista, 0, true);
                    $salida = $lista[0][2];

                    //Valores predeterminados para la busqueda
                    $maxpeso = $lista[0][0];

                    //Busco las mejores salida
                    foreach ($lista as $indice => $valor) {
                        if ($valor[0] >= $maxpeso * $factor) {
                            if (!in_array($valor[3], $datos['hashes'])){
                                array_push($posibles, $valor);
                            }
                        } else {
                            break;
                        }
                    }
                    if(count($posibles)!=0){
                        $i = array_rand($posibles);
                        array_push($datos['hashes'], $posibles[$i][3]);
                        $salida = $posibles[$i][2];

                    }
                }
            }
        }
        foreach ($posibles as $posible){
            array_push($salidaNueva,$posible[2]);

        }

        // Guardamos las entradas anteriores
        $datos['entradas_previas'][2] = $datos['entradas_previas'][1];
        $datos['entradas_previas'][1] = $datos['entradas_previas'][0];
        $datos['entradas_previas'][0] = $entradasintags;
        $datos['res_previa']          = $salida;
        $datos['salida'] = $salida;


        if ($controltomado != false)
            $datos['salida'] = "";

        // Si nos envia un {greeting} o una tag custom, don't save it
        $reg = "/{(.*)}/";
        preg_match($reg, $entradasintags, $coincidencias, PREG_OFFSET_CAPTURE);

        //if ($auto || !empty($coincidencias))
        //  $entradasintags = "";

        //$this->guardarConversacion($datos["com_id"], time(), $datos["his_sessiontimestamp"], $datos["cli_phone"], $entradasintags, $datos["salida"], json_encode($datos), $camid, $idmo, $auxid);

        return $salidaNueva;
    }

    public function messageFormat($input) {
        $input = trim($input);

        $input = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $input
        );

        $input = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $input
        );

        $input = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $input
        );

        $input = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $input
        );

        $input = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $input
        );

        $input = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç'),
            array('n', 'N', 'c', 'C',),
            $input
        );

        $input = str_replace(
            array("\\", "¨", "º", "-", "~",
                "#", "@", "|", "!", "\"",
                "·", "$", "%", "&", "/",
                "(", ")", "?", "'", "¡",
                "¿", "[", "^", "`", "]",
                "+", "}", "{", "¨", "´",
                ">", "< ", ";", ",", ":",
                "."),
            '',
            $input
        );

        return $input;
    }

    public function getSubjects($entrada){

        $limit=500;
        $resultado = array();
        //Filtrando la entrada
        $entradaF = $this->filtrar($entrada);
        //Extrae las palabras de la entrada
        $palabrasEntradaF = explode(' ', $entradaF);
        //Numero de palabras que forman la entrada
        $numPalsEntrada = count($palabrasEntradaF);

        //Calculamos el numero de sujetos
        $numsujetos = round($numPalsEntrada*0.4);
        if ($numsujetos<1){ $numsujetos=1; }
        if ($numsujetos>5){ $numsujetos=5; }
        $sujetos=array();

        $aux = $entradaF;
        $aux=preg_replace("/[[:punct:]]+/i", " ", $aux);
        $aux = trim($aux);
        $aux = preg_replace("/[[:space:]]+/i", " ", $aux);


        for($i=0;$i<$numsujetos;$i++){
            //Puntua las palabras
            $tuplas = $this->puntuar($aux);
            //Sujeto de la entrada
            $sujeto = $this->sujetode($aux, $tuplas);
            $aux = preg_replace('/([[:space:]]|^){1}'.$sujeto.'([[:space:]]|$){1}/i', " ", $aux);
            $aux = rtrim($aux);
            $aux = preg_replace("/[[:space:]]+/i"," ",$aux);
            array_push($sujetos, $sujeto);
        }

        return $sujetos;
    }

    public function is_family($word1, $word2){

        //busco en groups si esta la word 1
        $em=$this->getDoctrine()->getManager();
        $wordsRepo = $em->getRepository("AppBundle:groups");
        $queryGroup = $wordsRepo->findBy(array('word'=>$word1));

        //$queryGroup=mysql_query("SELECT * FROM familias WHERE `word`='$word1'");
        //while($res2= mysql_fetch_assoc($queryGroup)){
        //  $gr1[]=$res2['groupTag'];
        //}
        $gr1 = Array();
        foreach ($queryGroup as $query){
            array_push($gr1, $query->getNamegroup());
        }

        $em=$this->getDoctrine()->getManager();
        $wordsRepo = $em->getRepository("AppBundle:groups");
        $queryGroup2 = $wordsRepo->findBy(array('word'=>$word2));
        //$queryGroup2=mysql_query("SELECT * FROM familias WHERE `word`='$word2'");
        $gr2 = Array();
        foreach ($queryGroup2 as $query){
            array_push($gr2, $query->getNamegroup());
        }

        if(count($gr1)>0 and count($gr2)>0){
            foreach($gr1 as $key=>$value){
                foreach($gr2 as $key2=>$value2){
                    if($value==$value2){
                        return true;
                    }
                }
            }
            return false;
        }else{
            return false;
        }

    }

    public function relaciones_grupo($entrada, $salida){

        echo "<br><br>".$entrada." ||||||| ".$salida;
        $efiltered=filtrar($entrada);
        $sfiltered=filtrar($salida);
        $exploded=explode(" " , $efiltered);
        $sxploded=explode(" " , $sfiltered);

        foreach($exploded as $key=>$value){
            $max=2/3;
            $i=0;
            $nval=strlen($value);
            foreach($sxploded as $key2=>$value2){
                $b=0;
                $i=0;
                while($i<$nval){
                    if($value{$i}==$value2{$i}){
                        $b++;
                    }
                    $i++;
                }
                $puntos=$b/$nval;
                if($puntos>$max){
                    echo "<br>$value $value2 $puntos<br>";
                    $similar=$value2;
                    $max=$puntos;
                }
            }
            if(!empty($similar)){
                $relaciones[]= $value." --> ".$similar;
                $entRel[]=$value;
                $salRel[]=$similar;
            }
        }

        if(count($entRel)==count($exploded)-1 and count($salRel)==count($sxploded)-1){
            $eDiff=array_diff($exploded, $entRel);
            $sDiff=array_diff($sxploded,$salRel);
            //print_R($sDiff);
            $relaciones[]=join('',$eDiff) ." ------>  ".join('',$sDiff);
            $eDiff=join('',$eDiff);
            $sDiff=join('',$sDiff);

            //meter en la db el nuevo grupo
            $em=$this->getDoctrine()->getManager();
            $groups = new groups();
            $groups->setWord($sDiff);
            $groups->setType('descartes');
            $date = new DateTime();
            $groups->setDate($date->getTimestamp());
            $groups->setNamegroup($eDiff);
            $em->persist($groups);
            $em->flush();

        }
        return $relaciones;
    }

    public function get_family($word1){
        $em=$this->getDoctrine()->getManager();
        $groupsRepo = $em->getRepository("AppBundle:groups");
        $queryGroup = $groupsRepo->findBy(array('word'=>$word1));

        $groups = $groupsRepo->findBy(array('groups'=> $queryGroup->getNamegroup()));


        /*$queryGroup=mysql_query("SELECT * FROM familias WHERE `word`='$word1'");
        while($res2=mysql_fetch_assoc($queryGroup)){
            $gr=$res2['groupTag'];
            $queryGroup2=mysql_query("SELECT * FROM familias WHERE `groupTag`='$gr'");
            while($res3=mysql_fetch_assoc($queryGroup2)){
                $groups[$gr][]=$res3['word'];

            }
        }*/

        if(count($groups)==0){
            return false;
        }else{
            return $groups;
        }

    }

    public function familias($groupTag){
        $em=$this->getDoctrine()->getManager();
        $groupsRepo = $em->getRepository("AppBundle:weightWords");
        $queryGroup = $groupsRepo->findAll();

        if($queryGroup > 2){
            foreach ($queryGroup as $query){
                if(strpos($query->getWord(), $groupTag != false)){
                    if(strlen($groupTag)>=round(strlen($query->getWord())/3)*2 and strlen($groupTag)<=strlen($query->getWord()) and strlen($groupTag)>1) {
                        $groups = new groups();
                        $groups->setWord($query->getWord());
                        $groups->setType('family');
                        $date = new DateTime();
                        $groups->setDate($date->getTimestamp());
                        $groups->setNamegroup($groupTag);
                        $em->persist($groups);
                        $em->flush();
                        $fact=round(strlen($query->getWord())/3)*2;
                        echo "<br> $query->getWord() :".$fact." $groupTag : ".strlen($groupTag);
                    }
                }

                }
        }

        $queryGroup=mysql_query("SELECT * FROM pesos WHERE palabra REGEXP '^$groupTag'");
        if(mysql_num_rows($queryGroup)>2){
            while($res2=mysql_fetch_assoc($queryGroup)){
                $pal=$res2['palabra'];
                if(strlen($groupTag)>=round(strlen($pal)/3)*2 and strlen($groupTag)<=strlen($pal) and strlen($groupTag)>1){
                    mysql_query("INSERT INTO `familias` (`word` ,`groupTag`) VALUES ( '$pal', '$groupTag' )");
                    $fact=round(strlen($pal)/3)*2;
                    echo "<br> $pal :".$fact." $groupTag : ".strlen($groupTag);
                }
            }
        }

    }
    //Saber el peso de una palabra ya guardada
    public function get_pesos($word){
        $em=$this->getDoctrine()->getManager();
        $weightRepo = $em->getRepository("AppBundle:weightWords");
        $weight = $weightRepo->findOneBy(array('word'=>$word));
        $weightOfWord = $weight->getWeight();
        return $weightOfWord;
    }


    public function iniciarconversacion($entrada, $data = null, $camid = 46, $idmo = 0, $auxid = NULL) {
        // Este va a ser el ID para saber donde empieza de la conversación
        $time = time();
        //$botData = $data["bot_data"];

        // Objetivos de la campaña
        //$campaignObjective = ClassRegistry::init('CampaignObjective');
        //$objectives = $campaignObjective->find('all', array('conditions' => array('cam_id' => $camid)));

        $convData = array(
            //'bot_type'	=> $data['bot_type'],
            //'com_id'	=> $data['com_id'],
            //'cli_phone'	=> $data['cli_phone'],
            'bot_type'	=> '',
            'com_id'	=> '',
            'cli_phone'	=> '',
            'his_sessiontimestamp' 	=> $time,
            // Personal Information
            'per_name'	=> '',
            'per_city'	=> '',
            'per_street'=> '',
            'per_age'	=> '',
            'per_job'	=> '',
            // Bot
            //'bot_id'	=> $data['bot_id'],
            //'bot_name'	=> $data['bot_name'],
            //'bot_city'	=> $data['bot_city'],
            //'bot_street'=> $data['bot_street'],
            //'bot_age'	=> $data['bot_age'],
            'bot_id'	=> '',
            'bot_name'	=> '',
            'bot_city'	=> '',
            'bot_street'=> '',
            'bot_age'	=> '',
            'cercade'	=> '',
            // Extra
            'respuesta'	=> '',
            'res_previa'=> '',
            'entradas_previas' => array('','',''),
            'orden'		=> 0 ,
            'controla'	=> '',
            'hashes'	=> array(),
            'objectives' => array()
        );
        /*if (!empty($objectives)) {
            foreach ($objectives AS $objective)
            {
                $id = $objective['CampaignObjective']['id'];
                $convData['objectives']['objective_'.$id] = 0;
            }
        }*/

        if (!empty($entrada)) {
            return $this->hablar($entrada, $convData, $camid, $idmo, $auxid);
        }

        return $convData;
    }



}
