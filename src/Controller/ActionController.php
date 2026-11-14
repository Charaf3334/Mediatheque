<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};

abstract class ActionController extends AbstractController
{
    protected function run(Request $request, callable $action, string $success, string $route): Response
    {
        if (!$this->isCsrfTokenValid('act', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        try {
            $action();
            $this->addFlash('success', $success);
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute($route);
    }
}
