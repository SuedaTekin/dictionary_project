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

        if ($query) {
            // Aranan kelimeyi büyük harfe çeviriyoruz
            $searchTerm = mb_strtoupper($query, 'UTF-8');
            
            $conn = $doctrine->getConnection();

            /**
             * 1. Sonuçları Getir
             * Limit ve Offset değerlerini doğrudan SQL içine koyarak 
             * 'Unknown column type' hatasından kurtuluyoruz.
             */
            $sql = "SELECT * FROM dictionary 
                    WHERE BINARY name = :searchTerm 
                    LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('searchTerm', $searchTerm);
            $results = $stmt->executeQuery()->fetchAllAssociative();

            // 2. Toplam Sayıyı Getir
            $sqlCount = 'SELECT COUNT(*) as total FROM dictionary WHERE BINARY name = :searchTerm';
            $stmtCount = $conn->prepare($sqlCount);
            $stmtCount->bindValue('searchTerm', $searchTerm);
            $totalCount = $stmtCount->executeQuery()->fetchOne();
        }

        $totalPages = ceil($totalCount / $limit);

        return $this->render('dictionary/results.html.twig', [
            'results' => $results,
            'query' => $query,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }
}