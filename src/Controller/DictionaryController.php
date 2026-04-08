<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class DictionaryController extends AbstractController
{
    #[Route('/search', name: 'app_dictionary_search')]
    public function search(Request $request, ManagerRegistry $doctrine): Response
    {
        $query = $request->query->get('q');
        $page = $request->query->getInt('page', 1);
        $limit = 3;
        $offset = ($page - 1) * $limit;

        $results = [];
        $totalCount = 0;
        $suggestion = null; 
        $randomWord = null;

        $conn = $doctrine->getConnection();

        if ($query) {
            $searchTerm = mb_strtoupper($query, 'UTF-8');

            $sql = "SELECT MIN(id) as id, name, MIN(description) as description FROM dictionary 
                    WHERE BINARY name = :searchTerm 
                GROUP BY name";
        
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('searchTerm', $searchTerm);
            $results = $stmt->executeQuery()->fetchAllAssociative();

            if (empty($results)) {
            $allWordsSql = "SELECT DISTINCT name FROM dictionary";
            $allWords = $conn->executeQuery($allWordsSql)->fetchFirstColumn();

            $shortest = -1;
            foreach ($allWords as $word) {
                $lev = levenshtein($searchTerm, $word);
                
                if ($lev <= 3 && $lev > 0) { 
                    if ($lev < $shortest || $shortest < 0) {
                        $suggestion = $word;
                        $shortest = $lev;
                    }
                }
            }
        }

        foreach ($results as &$item) {
            $item['description'] = preg_replace('/\(([^)]+)\)/', '<span class="word-info">($1)</span>', $item['description']);
            $item['description'] = preg_replace('/(\d+\.)/', '<br>$1', $item['description']);
            $item['description'] = preg_replace('/^<br>/', '', $item['description']);
        }

        $sqlCount = 'SELECT COUNT(DISTINCT name) as total FROM dictionary WHERE BINARY name = :searchTerm';
        $stmtCount = $conn->prepare($sqlCount);
        $stmtCount->bindValue('searchTerm', $searchTerm);
        $totalCount = $stmtCount->executeQuery()->fetchOne();
        
    } else {
        $sqlRandom = "SELECT name, description FROM dictionary ORDER BY RAND() LIMIT 1";
        $randomWord = $conn->executeQuery($sqlRandom)->fetchAssociative();
        
        if ($randomWord) {
            $randomWord['description'] = preg_replace('/\(([^)]+)\)/', '<span class="word-info">($1)</span>', $randomWord['description']);
            $randomWord['description'] = preg_replace('/(\d+\.)/', '<br>$1', $randomWord['description']);
        }
    }

    return $this->render('dictionary/results.html.twig', [
        'results' => array_slice($results, $offset, $limit),
        'query' => $query,
        'suggestion' => $suggestion,
        'randomWord' => $randomWord,
        'currentPage' => $page,
        'totalPages' => ceil($totalCount / $limit),
    ]);
}

    #[Route('/suggest', name: 'app_dictionary_suggest')]
    public function suggest(Request $request, ManagerRegistry $doctrine): Response
    {
        $query = $request->query->get('q', '');
        $suggestions = [];

        if (mb_strlen($query, 'UTF-8') >= 1) { 
            $searchTerm = mb_strtoupper($query, 'UTF-8');
            $conn = $doctrine->getConnection();
            $sql = "SELECT DISTINCT name FROM dictionary WHERE BINARY name LIKE :searchTerm LIMIT 10";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('searchTerm', $searchTerm . '%');
            $suggestions = $stmt->executeQuery()->fetchFirstColumn();
        }

        return $this->json($suggestions);
    }
}