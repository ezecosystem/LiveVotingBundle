<?php

namespace Netgen\LiveVotingBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Netgen\LiveVotingBundle\Entity\Question;
use Netgen\LiveVotingBundle\Form\QuestionType;

/**
 * Question controller.
 *
 */
class QuestionAdminController extends Controller
{

    /**
     * Lists all Question entities.
     *
     */
    public function indexAction($event_id)
    {
        $em = $this->getDoctrine()->getManager();
        $event = $em->getRepository('LiveVotingBundle:Event')->find($event_id);
        $entities = $em->getRepository('LiveVotingBundle:Question')->findBy(array('event'=>$event));

        $that = $this;
        return $this->render('LiveVotingBundle:Question:index.html.twig', array(
            'entities' => array_map(
                function($ent) use ($that) {
                   return array($ent, $that->createEnableDisableForm($ent)->createView());
                }, $entities),
            'event' => $event
        ));
    }
    /**
     * Creates a new Question entity.
     *
     */
    public function createAction(Request $request, $event_id)
    {
        $entity = new Question();
        $event = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->find($event_id);
        $entity->setEvent($event);
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
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
     *
     * @param Question $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Question $entity)
    {
        $form = $this->createForm(new QuestionType(), $entity, array(
            'action' => $this->generateUrl('admin_question_create', array('event_id'=>$entity->getEvent()->getId())),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Add questions'));

        return $form;
    }

    /**
     * Displays a form to create a new Question entity.
     */
    public function newAction($event_id)
    {
        $entity = new Question();
        $event = $this->getDoctrine()->getRepository('LiveVotingBundle:Event')->find($event_id);
        $entity->setEvent($event);

        $form   = $this->createCreateForm($entity);
        $form->remove('votingEnabled');
        return $this->render('LiveVotingBundle:Question:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'event_id' => $event_id
        ));
    }


    /**
     * Displays a form to edit an existing Question entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LiveVotingBundle:Question')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Question entity.');
        }

        $editForm = $this->createEditForm($entity);

        // Not needed in edit page
        $editForm->remove('votingEnabled');

        return $this->render('LiveVotingBundle:Question:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView()
        ));
    }

    /**
    * Creates a form to edit a Question entity.
    *
    * @param Question $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Question $entity)
    {
        $form = $this->createForm(new QuestionType(), $entity, array(
            'action' => $this->generateUrl('admin_question_update', array('id' => $entity->getId())),
            'method' => 'PUT',

        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Question entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LiveVotingBundle:Question')->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Question entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entity->upload();
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
     */
    public function createEnableDisableForm(Question $entity){
        $form = $this->createFormBuilder();
        $form->setMethod('PUT');
        $form->setAction($this->generateUrl('admin_question_vote_enable', array('id'=>$entity->getId())));
        if($entity->getVotingEnabled()==False)
            $form->add('disable', 'submit',  array('label'=>'Disabled', 'attr'=>array('class'=>'btn btn-danger')));
        else
            $form->add('enable', 'submit',  array('label'=>'Enabled', 'attr'=>array('class'=>'btn btn-success')));

        return $form->getForm();
    }

    /**
     * Action that enabled and disables question.
     */
    public function enableDisableAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LiveVotingBundle:Question')->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Question entity.');
        }

        $form = $this->createEnableDisableForm($entity, 'enabled', array());
        $form->handleRequest($request);
        if ($form->isValid()) {
            if($form->getClickedButton()->getName()=='disable'){
                $entity->setVotingEnabled(true);
            }else{
                $entity->setVotingEnabled(false);
            }
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_question', array('event_id' => $entity->getEvent()->getId())));

    }

    public function deleteAction($id){
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LiveVotingBundle:Question')->find($id);
        $eventId = $entity->getEvent()->getId();

        if (!$entity) {
            throw $this->createNotFoundException('Question is already removed.');
        }

        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('admin_question', array('event_id' => $eventId )));
    }

}