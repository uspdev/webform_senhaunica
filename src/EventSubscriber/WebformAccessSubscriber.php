<?php

namespace Drupal\webform_senhaunica\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\webform\WebformInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Database\Connection;

class WebformAccessSubscriber implements EventSubscriberInterface {

  private LoggerChannelInterface $logger;
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    private MessengerInterface $messenger,
    private Connection $database,
    LoggerChannelFactoryInterface $loggerFactory, ) {
    $this->routeMatch = $routeMatch;
    $this->messenger = $messenger;
    $this->logger = $loggerFactory->get('webform_senhaunica');
  }

  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onRequest', 30],
    ];
  }

  public function onRequest(RequestEvent $event): void {

    if (!$event->isMainRequest()) {
      return;
    }

    $route_name = $this->routeMatch->getRouteName();

    // Intercepta apenas a visualização de Webforms.
    if ($route_name !== 'entity.webform.canonical') {
      return;
    }

    $webform = $this->routeMatch->getParameter('webform');

    if (!$webform instanceof WebformInterface) {
      return;
    }

    $habilitado = (bool) $webform->getThirdPartySetting(
      'webform_senhaunica',
      'habilitar_senhaunica',
      FALSE
    );

    if (!$habilitado) {
      return;
    }

    $session = $event->getRequest()->getSession();

    // Armazenar o webform_id na sessão
    $session->set('senhaunica_webform_id', $webform->id());

    $webform_id = $webform->id();

    // Verificar se o usuário já respondeu este formulário (marcador em sessão)
    $respondidos = $session->get('webform_senhaunica_respondidos', []);

    if (!is_array($respondidos)) {
      $respondidos = [];
    }

    if (in_array($webform_id, $respondidos, TRUE)) {
      // Remover mensagem de sucesso anterior
      $this->messenger->deleteByType('status');

      $event->setResponse(
        new RedirectResponse('/ja-respondeu')
      );

      return;
    }

    // Verificar se o usuário já está autenticado
    $numero_usp = $session->get('senhaunica_numero_usp');

    if ($numero_usp) {

      if (!is_string($numero_usp) && !is_int($numero_usp)) {
        return;
      }
      $numero_usp = (string) $numero_usp;
      
      $query = $this->database
        ->select('webform_senhaunica', 'ws')
        ->fields('ws', ['id'])
        ->condition('numero_usp', $numero_usp)
        ->condition('webform_id', $webform_id)
        ->range(0, 1);

      $result = $query->execute();
      $ja_respondeu = $result ? $result->fetchField() : NULL;

      if ($ja_respondeu) {

        $respondidos[] = $webform_id;
        $session->set('webform_senhaunica_respondidos', $respondidos);

        $this->messenger->deleteByType('status');

        $this->logger->notice(
          'Webform @id acessado por numero_usp @usp (autorizado)',
          [
            '@id' => $webform_id,
            '@usp' => $numero_usp,
          ]
        );

        $event->setResponse(new RedirectResponse('/ja-respondeu'));
        return;
      }

      $this->logger->notice(
        'Webform @id acessado por numero_usp @usp (autorizado)',
        [
          '@id' => $webform_id,
          '@usp' => $numero_usp,
        ]
      );

      return;
    }

    // Usuário não autenticado, redirecionar para o callback (iniciar autenticação)
    $this->logger->notice(
      'Webform @id acessado - redirecionando para autenticação',
      ['@id' => $webform_id],
    );

    // Remover mensagens anteriores antes de redirecionar
    $this->messenger->deleteByType('status');

    $event->setResponse(
      new RedirectResponse('/callback')
    );
  }

}
