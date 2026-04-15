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
class DictionaryController extends AbstractController
{
    #[Route('/', name: 'admin_dictionary_index', methods: ['GET'])]
    public function index(DictionaryRepository $dictionaryRepository, Request $request): Response
    {  
        $page = $request->query->getInt('page', 1);
        if ($page < 1) $page = 1;

        $limit = 10; 
        $result = $dictionaryRepository->findPaginated($page, $limit);

        return $this->render('admin/dictionary/index.html.twig', [
            'dictionaries' => $result['data'],
            'current_page' => $page,
            'total_pages'  => (int) ceil($result['total'] / $limit),
        ]);
    }

    #[Route('/new', name: 'admin_dictionary_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $dictionary = new Dictionary();
        $form = $this->createForm(DictionaryType::class, $dictionary);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($dictionary);
            $entityManager->flush();

            $this->addFlash('success', 'Kelime başarıyla eklendi!');
            return $this->redirectToRoute('admin_dictionary_index');
        }

        return $this->render('admin/dictionary/new.html.twig', [
            'dictionary' => $dictionary,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_dictionary_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dictionary $dictionary, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DictionaryType::class, $dictionary);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Kelime başarıyla güncellendi!');
            return $this->redirectToRoute('admin_dictionary_index');
        }

        return $this->render('admin/dictionary/edit.html.twig', [
            'dictionary' => $dictionary,
            'form' => $form->createView(),
        ]);
    }
}   