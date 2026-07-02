<?php

declare(strict_types=1);

namespace Drupal\webform_senhaunica\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Configure Webform Senha Única settings for this site.
 */
final class SettingsForm extends ConfigFormBase {
  private LoggerChannelInterface $logger;
  public function __construct(
    LoggerChannelFactoryInterface $loggerFactory, ) {
    $this->logger = $loggerFactory->get('webform_senhaunica');
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'webform_senhaunica_settings';
  }

  /**
   * {@inheritdoc}
   * 
   * @return string[]
   */
  protected function getEditableConfigNames(): array {
    return ['webform_senhaunica.settings'];
  }

  /**
   * {@inheritdoc}
   * 
   * @param array<string, mixed> $form
   * @return array<mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('webform_senhaunica.settings');

    $form['identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Identificador'),
      '#default_value' => $config->get('identifier'),
      '#required' => TRUE,
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#default_value' => $config->get('secret'),
      '#required' => TRUE,
    ];

    $form['callback_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Callback ID'),
      '#default_value' => $config->get('callback_id'),
      '#required' => TRUE,
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $config->get('url'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   * 
   * @param array<string, mixed> $form
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if ($form_state->getValue('example') === 'wrong') {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('The value is not correct.'),
    //     );
    //   }
    // @endcode
    //parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   * 
   * @param array<mixed> $form
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $this->logger->notice('submitForm executado');

    $this->config('webform_senhaunica.settings')
      ->set('identifier', $form_state->getValue('identifier'))
      ->set('secret', $form_state->getValue('secret'))
      ->set('callback_id', $form_state->getValue('callback_id'))
      ->set('url', $form_state->getValue('url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
