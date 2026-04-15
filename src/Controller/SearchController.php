<?php

namespace App\Controller;

use App\Entity\Dictionary;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function search(Request $request, EntityManagerInterface $em): Response
    {
        $q = $request->query->get('q');

        $results = [];

        if ($q) {
            $results = $em->getRepository(Dictionary::class)
                ->createQueryBuilder('d')
                ->where('d.name LIKE :q')
                ->setParameter('q', '%'.$q.'%')
                ->getQuery()
                ->getResult();
        }

        return $this->render('search/index.html.twig', [
            'results' => $results,
            'query' => $q
        ]);
    }
}