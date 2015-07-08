<?php

/*
 * This file is part of the Netgen LiveVoting bundle.
 *
 * https://github.com/netgen/LiveVotingBundle
 * 
 */

namespace Netgen\LiveVotingBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Netgen\LiveVotingBundle\Entity\Question;
use Netgen\LiveVotingBundle\Form\QuestionType;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Question controller.
 */
class QuestionAdminController extends Controller
{

    /**
     * Lists all Question entities.
     */
    public function indexAction($event_id)
    {
        $em = $this->getDoctrine()->getManager();
        $event = $em->getRepository('LiveVotingBundle:Event')->find($event_id);
        $entities = $em->getRepository('LiveVotingBundle:Question')->findBy(array('event' => $event));
        
        if($entities)
        {
            $status = $entities[0]->getVotingEnabled();            
        }
        else $status = 2; //hide enable-disable button

        $that = $this;
        return $this->render('LiveVotingBundle:Question:index.html.twig', array(
            'entities' => array_map(
                function($ent) use ($that) 
                {
                   return array($ent, $that->createEnableDisableForm($ent)->createView());
                }, $entities),
            'event' => $event,
            'status' => $status
        ));
    }

    /**
     * Creates a new Question entity.
     */
    public function createAction(Request $request, $event_id)
    {
        $entity = new Question();
        $event = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->find($event_id);
        $entity->setEvent($event);
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) 
        {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_question', array('event_id'=>$event_id)));
        }

        return $this->render('LiveVotingBundle:Question:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Question entity.
     * @param Question $entity The entity
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Question $entity)
    {
        $form = $this->createForm(new QuestionType(), $entity, array(
            'action' => $this->generateUrl('admin_question_create', array('event_id'=>$entity->getEvent()->getId())),
            'method' => 'POST',
        ));

        return $form;
    }

    /**
     * Displays a form to create a new Question entity.
     * @param Event ID $event_id
     */
    public function newAction($event_id)
    {
        $entity = new Question();
        $event = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->find($event_id);
        $entity->setEvent($event);

        $form = $this->createCreateForm($entity);
        return $this->render('LiveVotingBundle:Question:new.html.twig', array(
            'event' => $event,
            'entity' => $entity,
            'form'   => $form->createView(),
            'event_id' => $event_id
        ));
    }


    /**
     * Displays a form to edit an existing Question entity.
     * @param Question ID $question_id
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LiveVotingBundle:Question')->find($id);

        if (!$entity) 
        {
            throw $this->createNotFoundException('Unable to find Question entity.');
        }

        $editForm = $this->createEditForm($entity);

        // Not needed in edit page
        $editForm->remove('votingEnabled');
        $editForm->remove('question_type');

        return $this->render('LiveVotingBundle:Question:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView()
        ));
    }


    /**
     * Displays a form to view all answers from users.
     * @param Question ID $id
     */
    public function viewAnswersAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $answerS = 0;
        $entity = $em->getRepository('LiveVotingBundle:Question')->find($id);
        $question = $entity->getQuestion();
        $question_type = $entity->getQuestionType();
        $answers = $entity->getAnswers();
        $answerN = count($answers);
        $prosjek = 0;

        if($question_type==0)
        {
            foreach ($answers as $answer) 
            {
                $answerS += $answer->getAnswer();
            }

            $prosjek = number_format((double)$answerS/$answerN,'2','.','');
        }
        else
        {
            foreach ($answers as $answer) 
            {
                if($answer->getAnswer() == 2)$answerS++;
            }

            $prosjek = (int)(($answerS/$answerN)*100);
        }

        $users = array();

        foreach($answers as $answer)
        {
            $tmp = $answer->getUser();
            $users[] = $tmp->getEmail();
        }

        if (!$entity) 
        {
            throw $this->createNotFoundException('Unable to find Question entity.');
        }


       return $this->render('LiveVotingBundle:Question:answers.html.twig', array(
            'entity'      => $entity,
            'question'    => $question,
            'type'        => $question_type,
            'answers'     => $answers,
            'users'       => $users,
            'prosjek'     => $prosjek
        )); 
    }

    /**
    * Creates a form for answer entity.
    * @param Question $entity The entity
    * @return \Symfony\Component\Form\Form The form
    */
    private function createAnswerForm(Question $entity)
    {
        $form = $this->createForm(new QuestionType(), $entity, array(
            'action' => $this->generateUrl('admin_question_update', array('id' => $entity->getId())),
            'method' => 'PUT',

        ));

        $form->add('submit','submit', array('label' => 'Update', 'attr' => array('class' => 'btn btn-large btn-primary')));

        return $form;
    }

    /**
    * Creates a form to edit a Question entity.
    * @param Question $entity The entity
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Question $entity)
    {
        $form = $this->createForm(new QuestionType(), $entity, array(
            'action' => $this->generateUrl('admin_question_update', array('id' => $entity->getId())),
            'method' => 'PUT',

        ));

        $form->add('submit', 'submit', array('label' => 'Update', 'attr' => array('class' => 'btn btn-large btn-primary')));

        return $form;
    }

    /**
     * Edits an existing Question entity.
     * @param Request $request
     * @param Question ID $id
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('LiveVotingBundle:Question')->find($id);

        if (!$entity) 
        {
            throw $this->createNotFoundException('Unable to find Question entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) 
        {
            $em->flush();

            return $this->redirect($this->generateUrl('admin_question', array('event_id' => $entity->getEvent()->getId())));
        }

        return $this->render('LiveVotingBundle:Question:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView()
        ));
    }

    /**
     * Creates form to create enable/disable form for question
     * so users can vote on it.
     * @param Question entity $entity
     */
    public function createEnableDisableForm(Question $entity)
    {
        $form = $this->createFormBuilder();
        $form->setMethod('PUT');
        $form->setAction($this->generateUrl('admin_question_vote_enable', array('id'=>$entity->getId())));
        
        if($entity->getVotingEnabled()==False)
        {
            $form->add('disable', 'submit',  array('label'=>'Disabled', 'attr'=>array('class'=>'btn btn-danger')));
        }

        else
        {
            $form->add('enable', 'submit',  array('label'=>'Enabled', 'attr'=>array('class'=>'btn btn-success')));
        }

        return $form->getForm();
    }

    /**
     * Action that enabled and disables question.
     * @param Request $request
     * @param Question ID $id
     */
    public function enableDisableAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('LiveVotingBundle:Question')->find($id);

        if (!$entity) 
        {
            throw $this->createNotFoundException('Unable to find Question entity.');
        }

        $form = $this->createEnableDisableForm($entity, 'enabled', array());
        $form->handleRequest($request);

        if ($form->isValid()) 
        {
            if($form->getClickedButton()->getName()=='disable')
            {
                $entity->setVotingEnabled(true);
            }

            else
            {
                $entity->setVotingEnabled(false);
            }
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_question', array('event_id' => $entity->getEvent()->getId())));

    }

    /** 
     * Deletes an existing Question entity.
     * @param Question ID $id
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('LiveVotingBundle:Question')->find($id);
        $eventId = $entity->getEvent()->getId();

        if (!$entity) 
        {
            throw $this->createNotFoundException('Question is already removed.');
        }

        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('admin_question', array('event_id' => $eventId )));
    }

    /**
     * Returns question results
     * @param $event_id Event ID
     */
    public function viewResultsAction($event_id)
    {
        $em = $this->getDoctrine()->getManager();
        $event = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->find($event_id);
            if(!$event) throw $this->createNotFoundException('Unknown event.');

        $questions = $em->getRepository('LiveVotingBundle:Question')->findAll($event_id);
            if(!$questions) throw $this->createNotFoundException('There are no questions for this event.');
        
        return $this->render('LiveVotingBundle:Question:results.html.twig',array(
                'event' => $event,
                'questions' => $questions,
                'live_results_questions_url' => $this->generateUrl('admin_result_questions_json', array('event_id' => $event_id)),
                'live_results_questions_yesNo_url' => $this->generateUrl('admin_result_questions_yesNo_json', array('event_id' => $event_id))
            ));

    }

    /**
    * Functions for answers 1-5
    * @param Request $request
    * @param Event ID $event_id
    */
    public function getResultsAction(Request $request, $event_id)
    {
        $event = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->find($event_id);

        $results = $this->get('live_voting_question.result')->getLiveResultsQuestion($event_id);
        return new JsonResponse($results);
    }

    public function getTableAction($event_id)
    {
        $event = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->find($event_id);
        $results = $this->get('live_voting_question.result')->getLiveResultsQuestion($event_id);

        usort($results['questions'], function($v1, $v2){
            $v1score = floatval($v1['answer']['average']);
            $v2score = floatval($v2['answer']['average']);
            if($v1score > $v1score) return -1;
            if($v1score < $v2score) return 1;
            return 0;
        });

        return $this->render('LiveVotingBundle:Question:table.html.twig', array(
            'questions' => $results
        ));

    }


    /**
    *   functions for answers yes/no
    */
    public function getResultsYesNoAction(Request $request, $event_id)
    {
        $event = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->find($event_id);

        $results = $this->get('live_voting_question_yesNo.result')->getLiveResultsQuestionYesNo($event_id);
        return new JsonResponse($results);
    }

    public function getTableYesNoAction($event_id)
    {
        $event = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->find($event_id);
        $results = $this->get('live_voting_question_yesNo.result')->getLiveResultsQuestionYesNo($event_id);

        usort($results['questions'], function($v1, $v2){
            $v1score = floatval($v1['answer']['average']);
            $v2score = floatval($v2['answer']['average']);
            if($v1score > $v1score) return -1;
            if($v1score < $v2score) return 1;
            return 0;
        });

        return $this->render('LiveVotingBundle:Question:table.html.twig', array(
            'questions' => $results
        ));

    }

    public function statusChangeAction($event_id, $status)
    {
        $em = $this->getDoctrine()->getManager();
        $event = $em->getRepository('LiveVotingBundle:Event')->find($event_id);
        $questions = $em->getRepository('LiveVotingBundle:Question')->findBy(array('event' => $event));
            
        /**
        * find all questions of the event and set votingEnabled value to current value of event
        */
        $newValue = 1;
        switch($status)
        {
            case 1:
                $newValue = true;
                break;
            case 0:
                $newValue = false;
                break;
        }

        foreach ($questions as $question) 
        {
            $question->setVotingEnabled($newValue);           
        }
        $em->persist($event);
        $em->flush();

        return $this->redirect($this->generateUrl('admin_question', array('event_id' => $event_id)));
    }
}
