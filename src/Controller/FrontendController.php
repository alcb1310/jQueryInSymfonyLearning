<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Controller to render a basic "homepage".
 */
class FrontendController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function homepage(SerializerInterface $serializerInterface)
    {
        return $this->render('frontend/homepage.html.twig', [
            'user' => $serializerInterface->serialize($this->getUser(), 'jsonld'),
        ]);
    }
}
