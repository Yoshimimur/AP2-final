<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PublicController
{
    // Zeigt Gästen nur PDFs von aktiven Zieladressen
    public function show(Request $request, Response $response, array $args): Response
    {
        $destination = QrDestination::findBySlug($args['name'] . '/' . $args['code']);
        if ($destination === null || !$destination->isActive()) {
            return $this->unavailable($response);
        }
        return $this->document($destination->getId(), $response);
    }

    // Angemeldete Benutzer dürfen PDFs auch bei pausierten Zieladressen ansehen
    public function preview(Request $request, Response $response, array $args): Response
    {
        return $this->document((int) $args['id'], $response);
    }

    // Gibt ein vorhandenes PDF aus oder verwendet die gemeinsame Fehlerseite
    private function document(int $destinationId, Response $response): Response
    {
        $document = Document::findByDestination($destinationId);
        $service = new FileService();
        if ($document === null) {
            return $this->unavailable($response);
        }
        $path = $service->path($document->getFilePath());
        if (!is_file($path) || is_link($path)) {
            return $this->unavailable($response);
        }
        return $service->output($document, $response);
    }

    // Verwendet dieselbe Meldung für fehlende oder nicht freigegebene Dokumente
    private function unavailable(Response $response): Response
    {
        $response->getBody()->write('Das Dokument ist derzeit nicht verfügbar.');
        return $response->withStatus(404)->withHeader('Content-Type', 'text/plain; charset=UTF-8');
    }
}
