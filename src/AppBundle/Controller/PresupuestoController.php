<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;


/**
 * @Route("/presupuesto")
 */

class PresupuestoController extends Controller
{

  public function indexAction($name)
    {
        return $this->render('', array('name' => $name));
    }





     /**
     * @Route("/presupuesto", name="presupuesto")
     * @Method({"GET", "POST"})
     */
    public function acometidasActivasAction(Request $request){

        $manager = $this->getDoctrine()->getManager();
        $conn = $manager->getConnection();


        $stmnt = $conn->prepare("SELECT idProyecto, nombre FROM proyecto");
        $stmnt->execute();


        $result = $stmnt->fetchAll();


        $proyectos = array("Todos" => 0) +
            array_combine(array_column($result, "nombre"), array_column($result, "idProyecto"));
        $capturaDatos = array();


        //Formulario que se enviara a la vista 
        $form = $this->createFormBuilder($capturaDatos)
            ->add("proyecto", ChoiceType::class, array(
                "choices" => $proyectos,
                "constraints" => new NotBlank(array("message" => "Seleccione un proyecto"))
            ))

           
         
            ->add('send', SubmitType::class, array("label"=>"Enviar"))
            ->add('pdf', SubmitType::class, array("label" => "Crear PDF"))
            ->getForm();


        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // data is an array with the name of the inputs as keys to its values
            $data = $form->getData();
            $conn = $this->getDoctrine()->getManager()->getConnection();
                dump($data["proyecto"]);


            if($data["proyecto"]!="Todos"){



                $proyecto = $conn->prepare("SELECT idProyecto, nombre, descripcion, fchaInicio, fechaFin, estado, 
                	montoTotal FROM proyecto WHERE idProyecto=:proyecto");

              
                $etapas = $conn->prepare("SELECT idEtapa, nombre, detalle, idProyecto, fechaInicio, fechaFIn, estado, 
                	totalEtapa FROM etapa WHERE idProyecto=:proyecto");


               
                $partidas = $conn->prepare("SELECT ep.idPartida, pa.nombre, ep.cantidad, ep.CD, ep.CI, ep.IVA, ep.PU, ep.subTotal
											FROM etapapartida ep
											INNER JOIN etapa e ON ep.idEtapa=e.idEtapa
											INNER JOIN partida pa ON ep.idPartida=pa.numero
											INNER JOIN proyecto p ON e.idProyecto=p.idProyecto
											WHERE e.idProyecto=:proyecto");
          
                


            }




            $proyecto->bindValue("proyecto", $data["proyecto"]);
            $etapas->bindValue("proyecto", $data["proyecto"]);
            $partidas->bindValue("proyecto", $data["proyecto"]);
            



            $proyecto->execute();
            $etapas->execute();
            $partidas->execute();


            $resultProyecto = $proyecto->fetchAll();
            $resultEtapas = $etapas->fetchAll();
            $resultPartidas = $resultPartidas->fetchAll();




            $res = array();


            for ($i = 0; $i < count($proyecto); $i++) {
                $res[] = array("acomActivas" => $resultAcomActivas[$i]['acometidasActivas'], "porAcometidas"=>$resultPorActivas[$i]['porActivas'],
                    "acomExistentes"=>$resultAcomExist[$i]['acomExistentes'],
                    "periodo" => $resultAcomActivas[$i]["periodo"],
                    "anio" => $resultAcomActivas[$i]["anio"]);
            }



         

            $periodoInicio = array_search($data["mesInicio"], $this->meses) . " " . $data["anioInicio"];
            $periodoFin = array_search($data["mesFin"], $this->meses) . " " . $data["anioFin"];
            $now = date("d/m/Y");


            if ($form->get("pdf")->isClicked()) {
                $snappy = $this->get("knp_snappy.pdf");
                $html = $this->renderView("RepTacticos/Reportes/reporte_acom_activas.html.twig",
                    array("data"=>$res, "tipoPeriodo" => $tipoPeriodo, "today" => $now,
                        "periodoInicio"=> $periodoInicio, "periodoFin" => $periodoFin, 'sector'=>$data["sector"]));
                $filename = "reportePDF";
                return new Response(
                    $snappy->getOutputFromHtml($html),
                    200,
                    array(
                        'Content-Type'          => 'application/pdf',
                        'Content-Disposition'   => 'inline; filename="'.$filename.'.pdf"'
                    )
                );
            }

            else if ($form->get("send")->isClicked()) {
                return $this->render('RepTacticos/CapturaDatos/PreviewTable/preview_acometidas_activas.html.twig', array(
                    'form' => $form->createView(), "pageHeader" => "Reporte semi-resumen de acometidas activas",
                    "data" => $res, "tipoPeriodo" => $tipoPeriodo
                ));
            }

        }

        return $this->render('Presupuesto/entrada_presupuesto.html.twig', array(
            'form' => $form->createView(), "pageHeader" => "Presupuesto de proyectos"
        ));
    }





}




?>