<?php

namespace Netgen\LiveVotingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Netgen\LiveVotingBundle\Entity\Event;
use Netgen\LiveVotingBundle\Entity\Presentation;
use Netgen\LiveVotingBundle\Entity\PresentationComment;


class CommentsController extends Controller
{
    public function getCommentAction($number)
    {
        // Fetch first ongoing event
        $event = $this->getDoctrine()->getManager()->createQuery(
            'SELECT e
            FROM LiveVotingBundle:Event e
            WHERE :datetime > e.begin
            AND :datetime < e.end
            AND e.event IS NOT null
            ')->setParameter('datetime', new \DateTime())->getResult();

        if (is_array($event) && !empty($event)) {
            if (reset($event) instanceof Event) {
                // Fetch event presentations
                $presentations = $this->getDoctrine()
                    ->getRepository('LiveVotingBundle:Presentation')
                    ->findBy(array('event'=>$event));

                $presentationIds = array();
                /** @var Presentation $presentation */
                foreach ($presentations as $presentation) {
                    $presentationIds[] = $presentation->getId();
                }

                $em = $this->getDoctrine()->getManager();

                // Fetch $number last presentations comments
                $presentationComments = $em->createQuery(
                    'SELECT c
                    FROM LiveVotingBundle:PresentationComment c
                    WHERE c.presentation IN (' . implode(',', array_map('intval', $presentationIds)) . ')
                    ORDER BY c.published DESC'
                )->setMaxResults($number)->getResult();

                $comments['comments'] = array();
                /** @var PresentationComment $comment */
                foreach($presentationComments as $comment) {
                    $message = $comment->getContent();
                    if (strlen($message) > 140) {
                        $message = substr($message, 0, 140) . '...';
                    }

                    $username = $comment->getUser()->getEmail() ?
                        substr($comment->getUser()->getEmail(), 0, strrpos($comment->getUser()->getEmail(), "@"))
                        :
                        $comment->getUser()->getUsername();
                    array_push($comments['comments'], array(
                        "content" => $message,
                        "user_display_name" => $username,
                        "first_character" => substr($username, 0, 1),
                        "presentation" => $comment->getPresentation()->getPresentationName()
                    ));
                }


            } else {
                $comments = array();
            }
        } else {
            $comments = array();
        }

        return new JsonResponse($comments);

    }
    
}