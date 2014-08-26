<?php

namespace Netgen\LiveVotingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Netgen\LiveVotingBundle\Entity\User;
use Netgen\LiveVotingBundle\Entity\Vote;
use Netgen\LiveVotingBundle\Entity\Event;


class PrizeDrawController extends Controller {

	public function indexAction(){

		$events = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->findAll();
		$allVotes = $this->getDoctrine()->getRepository('LiveVotingBundle:Vote')->findAll();

		return $this->render('LiveVotingBundle:PrizeDraw:index.html.twig',
            array(
            	'events' => $events,
            	'allVotes' => $allVotes
            	)
        );
	}

	public function generatePoolAction(Request $request){

		
		return $this->render('LiveVotingBundle:PrizeDraw:generatePool.html.twig');

	}


}

?>