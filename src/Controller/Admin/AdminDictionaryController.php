<?php

namespace App\Controller\Admin;

use App\Entity\Dictionary;
use App\Form\DictionaryType;
use App\Repository\DictionaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/dictionary')]
class AdminDictionaryController extends AbstractController
{
    #[Route('/', name: 'admin_dictionary_index')]
    public function index(DictionaryRepository $repository, Request $request): Response 
    {
        $searchTerm = trim($request->query->get('search', ''));
        $page = $request->query->getInt('page', 1);
        if ($page < 1) $page = 1;

        $limit = 10; 

        $result = $repository->findPaginated($page, $limit, $searchTerm !== '' ? $searchTerm : null);

        $totalItems = $result['total'];
        $totalPages = (int) ceil($totalItems / $limit);

        return $this->render('admin/dictionary/index.html.twig', [
            'dictionaries' => $result['data'],
            'current_page' => $page,
            'total_pages'  => $totalPages,
        ]);
    }

    #[Route('/new', name: 'admin_dictionary_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $dictionary = new Dictionary();
        $form = $this->createForm(DictionaryType::class, $dictionary);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($dictionary);
            $em->flush();
            return $this->redirectToRoute('admin_dictionary_index');
        }

        return $this->render('admin/dictionary/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}