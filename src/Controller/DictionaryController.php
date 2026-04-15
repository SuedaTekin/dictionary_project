<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Dictionary; 
use App\Repository\DictionaryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class DictionaryController extends AbstractController
{
    #[Route('/search', name: 'app_dictionary_search', methods: ['GET'])]
    public function search(Request $request, ManagerRegistry $doctrine): Response
    {
        $query = $request->query->get('q');
        $page = $request->query->getInt('page', 1);
        $limit = 5; 
        $offset = ($page - 1) * $limit;

        $results = [];
        $totalCount = 0;
        $suggestion = null;
        $randomWord = null;

        $entityManager = $doctrine->getManager();
        $repository = $entityManager->getRepository(Dictionary::class);

        if ($query) {
            $searchTerm = mb_strtoupper($query, 'UTF-8');

            $qb = $repository->createQueryBuilder('d');
            $qb->select('MIN(d.id) as id', 'd.name', 'MIN(d.description) as description')
               ->where('UPPER(d.name) LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $searchTerm . '%')
               ->groupBy('d.name')
               ->setFirstResult($offset)
               ->setMaxResults($limit);

            $results = $qb->getQuery()->getArrayResult();

            $countQb = $repository->createQueryBuilder('d');
            $totalCount = $countQb->select('COUNT(DISTINCT d.name)')
                                  ->where('UPPER(d.name) LIKE :searchTerm')
                                  ->setParameter('searchTerm', '%' . $searchTerm . '%')
                                  ->getQuery()
                                  ->getSingleScalarResult();

            if (empty($results)) {
                $allWords = $repository->createQueryBuilder('d')
                    ->select('DISTINCT d.name')
                    ->getQuery()
                    ->getSingleColumnResult();

                $shortest = -1;
                foreach ($allWords as $word) {
                    $lev = levenshtein($searchTerm, mb_strtoupper($word,'UTF-8'));
                    if ($lev <= 3 && $lev > 0) {
                        if ($lev < $shortest || $shortest < 0) {
                            $suggestion = $word;
                            $shortest = $lev;
                        }
                    }
                }
            }

            foreach ($results as &$item) {
                $item['description'] = $this->formatDescription($item['description']);
            }
        } else {
            // İlk açılışta günün kelimesi
            $conn = $doctrine->getConnection();
            $sqlRandom = "SELECT name, description FROM dictionary ORDER BY RAND() LIMIT 1";
            $randomWord = $conn->executeQuery($sqlRandom)->fetchAssociative();

            if ($randomWord) {
                $randomWord['description'] = $this->formatDescription($randomWord['description']);
            }
        }

        return $this->render('dictionary/results.html.twig', [
            'results' => $results, 
            'query' => $query,
            'suggestion' => $suggestion,
            'randomWord' => $randomWord,
            'currentPage' => $page,
            'totalPages' => ceil($totalCount / $limit),
        ]);
    }

    #[Route('/random-word', name: 'app_dictionary_random_word', methods: ['GET'])]
    public function getRandomWord(ManagerRegistry $doctrine): JsonResponse
    {
        $conn = $doctrine->getConnection();
        $sqlRandom = "SELECT name, description FROM dictionary ORDER BY RAND() LIMIT 1";
        $randomWord = $conn->executeQuery($sqlRandom)->fetchAssociative();

        if ($randomWord) {
            $randomWord['description'] = $this->formatDescription($randomWord['description']);
            return $this->json($randomWord);
        }

        return $this->json(['error' => 'Kelime bulunamadı'], 404);
    }

    #[Route('/autocomplete', name: 'app_autocomplete')]
    public function autocomplete(Request $request, DictionaryRepository $repo): JsonResponse
    {
        $query = $request->query->get('q');
        if (!$query) return $this->json([]);

        $results = $repo->createQueryBuilder('d')
            ->where('LOWER(d.name) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $data = array_map(fn($item) => $item->getName(), $results);
        return $this->json($data);
    }

    #[Route('/suggest', name: 'app_dictionary_suggest')]
    public function suggest(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $query = $request->query->get('q', '');
        $suggestions = [];

        if (mb_strlen($query, 'UTF-8') >= 1) {
            $repository = $doctrine->getManager()->getRepository(Dictionary::class);
            $suggestions = $repository->createQueryBuilder('d')
                ->select('DISTINCT d.name')
                ->where('LOWER(d.name) LIKE LOWER(:searchTerm)')
                ->setParameter('searchTerm', '%' . $query . '%')
                ->setMaxResults(10)
                ->getQuery()
                ->getSingleColumnResult();
        }
        return $this->json($suggestions);
    }

    private function formatDescription(?string $description): string
    {
        if (!$description) return '';
        $description = preg_replace('/\(([^)]+)\)/', '<span class="word-info">($1)</span>', $description);
        $description = preg_replace('/(\d+\.)/', '<br>$1', $description);
        return preg_replace('/^<br>/', '', $description);
    }
}