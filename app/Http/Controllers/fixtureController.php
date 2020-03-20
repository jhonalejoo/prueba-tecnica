<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpParser\Node\Scalar\String_;
use function GuzzleHttp\Psr7\str;

class fixtureController extends Controller
{
    public function getFixture(){

        $client = new \GuzzleHttp\Client();
        $request = $client->request('GET', 'https://filesstaticpulzo.s3.us-west-2.amazonaws.com/pulzo-lite/deportes.todos.agenda.pulzo.xml');
        $request = $request->getBody()->getContents();
        $matches = new \SimpleXMLElement($request);

        $matches=$matches->partido;
        $idMatches=[];
        $fixture=[];

        foreach ($matches as $match){
            $matchAttributes=$match->attributes();
            if(in_array($matchAttributes->idCampeonato,$idMatches)){
               $fixture=$this->insertArray($match,$fixture);
            }else{
                $fixture=$this->insertArray($match,$fixture);
                array_push($idMatches,(string) $matchAttributes->idCampeonato);
            }
        }
        //array_unshift($fixture,'Grupo Destacado');
        return json_encode($fixture);
    }
    public function insertArray($match,$fixture){

        $matchAttributes=$match->attributes();
        $key=(String)$matchAttributes->nombreCampeonato;

        if((string)$matchAttributes->nombreCategoria =='UEFA Champions League' ||
            ((string)$matchAttributes->nombreCategoria =='Colombia - Primera División'
                && (($match->visitante[0] == 'Junior' || $match->visitante[0] == 'Deportivo Cali' )
            || ($match->local[0] == 'Junior' || $match->local[0] == 'Deportivo Cali' )
            ))
        ){
            $fixture['Grupo Destacado'][$key][]=$this->parseJson($match);
        }
        $fixture[$key][]=$this->parseJson($match);

        return $fixture;
    }

    public function parseJson($match){
        $matchAttributes=$match->attributes();

        $fixture=[
          "id"=>  (int)$matchAttributes->id,
            "fecha" =>(string) $matchAttributes->fecha ,
            "hora"=>(string)$matchAttributes->hora,
            "liga"=>(string)$matchAttributes->nombreCategoria,
            "urlMatch"=>$match->medios->medio ? (string)$match->medios->medio->attributes()->nombre :"sin dato",
            "estado" =>(string)$match->estado[0],
            "local"=> [
                "fullName"=>(string) $match->local[0],
                "shortName" =>(string) $match->local->attributes()->sigla,
             //"escudo",
             "goles" => (string) $match->goleslocal->attributes(),
             "penales" => (string) $match->golesDefPenaleslocal->attributes()
            ],
            "visitante"=> [
                "fullName" => (string) $match->visitante[0],
                "shortName" => (string) $match->visitante->attributes()->sigla,
                //"escudo",
                "goles" => (string) $match->golesvisitante->attributes(),
                "penales" => (string) $match->golesDefPenalesvisitante->attributes()->attributes()
            ],
        ];
        return $fixture;
    }
}
