<?php

/*
 * This file is part of the Netgen LiveVoting bundle.
 *
 * https://github.com/netgen/LiveVotingBundle
 *
 */

namespace Netgen\LiveVotingBundle\Controller;

use Netgen\LiveVotingBundle\Entity\Event;
use Netgen\LiveVotingBundle\Entity\Presentation;
use Netgen\LiveVotingBundle\Entity\Question;
use Netgen\LiveVotingBundle\Entity\User;
use Netgen\LiveVotingBundle\Entity\Vote;
use Netgen\LiveVotingBundle\Entity\Answer;
use Netgen\LiveVotingBundle\Exception\JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Netgen\LiveVotingBundle\Entity\PresentationComment;
use Netgen\LiveVotingBundle\Form\PresentationCommentType;

use Netgen\LiveVotingBundle\Form\PresentationImageType;
use Netgen\LiveVotingBundle\Entity\PresentationImage;

/**
 * Event controller. (user)
 */
class EventController extends Controller
{
    /**
     * Returns json response object which contain event presentations data
     * @param $request Request
     * @param $event_id Event ID
     * @return $response Event presentations in JSON
     */
    public function eventStatusAction(Request $request, $event_id)
    {
        try
        {
            $userByToken = $this->get('security.context')->getToken()->getUser();
            $userId = $userByToken->getId();
            $user = $this->getDoctrine()->getRepository('LiveVotingBundle:User')->find($userId);
            $event = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->find($event_id);
            if(!$event)
            {
                throw new JsonException(array('error'=>1, 'errorMessage'=>'Non existing event.'));
            }
            $response = $this->get('live_voting.handleRequest')->validateEventStatus($event, $user);

            // fetching presentations for current event
            $presentations = $this->getDoctrine()
                ->getRepository('LiveVotingBundle:Presentation')
                ->findBy(array('event'=>$event));

            $response['presentations']=array();

            // adding presentations to response array
            $em = $this->getDoctrine()->getRepository('LiveVotingBundle:Vote');
            foreach($presentations as $presentation)
            {
                $vote = $em->findOneBy(array('user'=>$user, 'presentation'=>$presentation));
                $rate = 0;

                if($vote)
                {
                    $rate = $vote->getRate();
                }
                $tmp = $this->getPresentationArray($presentation, $rate);
                array_push($response['presentations'], $tmp);
            }
            return new JsonResponse($response);
        }

        catch(JsonException $exception)
        {
            return new JsonResponse(unserialize($exception->getMessage()));
        }

    }

    /**
    * Returns presentation data array
    * @param $presentation Presentation object
    * @param $rate Presentation rate
    * @return Presentation data array
    */

    protected function getPresentationArray(Presentation $presentation, $rate)
    {
        return array(
            'presentationId' => $presentation->getId(),
            'presenterName' => $presentation->getPresenterName(),
            'presenterSurname' => $presentation->getPresenterSurname(),
            'presentationName' => $presentation->getPresentationName(),
            'presentationDescription' => $presentation->getDescription(),
            'votingEnabled' => $presentation->getVotingEnabled(),
            'presentationId' => $presentation->getId(),
            'image' =>  $presentation->getImage(),
            'presenterRate' => $rate,
            'comments' => $this->getCommentsArray($presentation->getPresentationComments())
        );
    }

    private function getCommentsArray($presentationComments)
    {
        $comments = array();
        foreach($presentationComments as $comment) {
            /**
             * @var $comment PresentationComment
             */
            array_push($comments, array(
                "content" => $comment->getContent(),
                "published_at" => $comment->getPublished()->format("d.m.Y H:m"),
                "user_display_name" =>
                    $comment->getUser()->getEmail() ?
                        substr($comment->getUser()->getEmail(), 0, strrpos($comment->getUser()->getEmail(), "@"))
                        :
                        $comment->getUser()->getUsername()
            ));
        }
        return $comments;
    }

    /**
     * Lists all Presentations on /user/event/{event_id} page
     * @param $request Request
     * @param $event_id Event ID
     */
    public function indexAction(Request $request, $event_id)
    {
        $session = $request->getSession();
        $session->start();
        $sessionId = $session->getId();
        $userByToken = $this->get('security.context')->getToken()->getUser();
        $userId = $userByToken->getId();
        $user = $this->getDoctrine()->getRepository('LiveVotingBundle:User')->find($userId);
        $event = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->find($event_id);
        if(!$event) {
            throw $this->createNotFoundException('The event does not exist!');
        }

        $entity = new PresentationComment();
        $form = $this->createForm(new PresentationCommentType(), $entity, array(
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Comment'));

        $entity2 = new PresentationImage();
        $imageForm = $this->createForm(new PresentationImageType(), $entity2, array(
            'method' => 'POST',
        ));

        $imageForm->add('submit', 'submit', array('label' => 'Comment'));

        return $this->render('LiveVotingBundle:Index:index.html.twig',
                array(
                  'event' => $event,
                  'form' => $form->createView(),
                  'imageForm' => $imageForm->createView()
                )
            );
    }

    /**
     * Returns json response object which contain event questions data
     * @param $request Request
     * @param $event_id Event ID
     */
    public function eventStatusQuestionAction(Request $request, $event_id)
    {
        try
        {
            $userByToken = $this->get('security.context')->getToken()->getUser();
            $userId = $userByToken->getId();
            $user = $this->getDoctrine()->getRepository('LiveVotingBundle:User')->find($userId);
            $event = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->find($event_id);
            $questions = $this->getDoctrine()->getRepository('LiveVotingBundle:Question')->findBy(array('event' => $event_id));
            $questionStatus = $questions[0]->getVotingEnabled();

            if(!$event)
                throw new JsonException(array('error'=>1, 'errorMessage'=>'Non existing event.'));

            $response = $this->get('live_voting.handleRequest')->validateEventQuestionStatus($event, $user, $questionStatus);
            $questions = $this->getDoctrine()
                ->getRepository('LiveVotingBundle:Question')
                ->findBy(array('event'=>$event));
            $response['questions'] = array();
            $em = $this->getDoctrine()->getRepository('LiveVotingBundle:Answer');

            foreach($questions as $question)
            {
                $answer = $em->findOneBy(array('user' => $user, 'question' => $question));
                $rate = 0;
                if($answer)
                    $rate = $answer->getAnswer();
                $tmp = $this->getQuestionArray($question, $rate);
                array_push($response['questions'], $tmp);
            }
            return new JsonResponse($response);
        }

        catch(JsonException $exception)
        {
            return new JsonResponse(unserialize($exception->getMessage()));
        }

    }

    /**
     * Returns question data array
     * @param $question Question object
     * @param $rate Question rate
     * @return Question data array
     */
    protected function getQuestionArray(Question $question, $rate){
        return array(
            'question' => $question->getQuestion(),
            'question_type' => $question->getQuestionType(),
            'votingEnabled' => $question->getVotingEnabled(),
            'questionId' => $question->getId(),
            'answer' => $rate
        );
    }

    /**
     * Lists all Questions on /user/question/{event_id} page
     * @param $request Request
     * @param $event_id Event ID
     */
    public function indexAnswerAction(Request $request, $event_id)
    {
        $session = $request->getSession();
        $session->start();
        $sessionId = $session->getId();
        $userByToken = $this->get('security.context')->getToken()->getUser();
        $userId = $userByToken->getId();
        $user = $this->getDoctrine()->getRepository('LiveVotingBundle:User')->find($userId);
        $event = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->find($event_id);
        return $this->render('LiveVotingBundle:Answer:index.html.twig', array(
            'event' => $event
        ));
    }

}
