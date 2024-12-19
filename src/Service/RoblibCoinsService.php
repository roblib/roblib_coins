<?php

namespace Drupal\roblib_coins\Service;

use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides COIN Compliant URLS.
 */
class RoblibCoinsService {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the RoblibCoin service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * Mint a coin for the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string
   *   A Coin link.
   */
  public function mintCoin(?NodeInterface $node) {
    if ($node === NULL || $node->bundle() != 'islandora_object') {
      return NULL;
    }
    $config = $this->configFactory->get('roblib_coins.settings');
    $terms = $config->get('term_settings');
    $coins_base_url = 'http://resolver.ebscohost.com/openurl';
    $params = [];
    $authors = $this->getFieldValues($node, $terms['authors']);
    $genre_name = strtolower($this->getFieldValues($node, 'field_genre'));
    if (!$authors || $genre_name == 'dissertation/thesis') {
      return NULL;
    }
    $params['authors'] = $authors;
    if (str_contains($genre_name, 'book') || str_contains($genre_name, 'conference')) {
      $params['isbn'] = $this->getFieldValues($node, $terms['isbn']);
    }
    else {
      $params['genre'] = 'article';
    }
    $title = $node->getTitle();
    $atitle = $this->getFieldValues($node, $terms['pub_title']);
    if ($atitle) {
      $params['title'] = $atitle;
      $params['atitle'] = $title;
    }
    else {
      $params['title'] = $title;
    }
    $date_issued = $this->getFieldValues($node, $terms['date_issued']);
    $params['date'] = explode('T', $date_issued)[0];
    $params['doi'] = $this->getFieldValues($node, $terms['doi']);
    $params['issn'] = $this->getFieldValues($node, $terms['issn']);
    $page_range = $this->getFieldValues($node, $terms['page_range']);
    $splitters = [',', '_', '-'];
    if ($page_range) {
      $page_range = str_replace($splitters, '|', $page_range);
      $range_parts = explode('|', $page_range);
      $params['spage'] = $range_parts[0];
      $params['pages'] = $range_parts[1] ?? $range_parts[0];
    }
    $params = array_filter($params);
    $options = ['absolute' => TRUE, 'query' => $params];
    $url = Url::fromUri($coins_base_url, $options);
    $proxy_url = 'http://proxy.library.upei.ca/login?url=' . $url->toString();
    $escaped_proxy_url = htmlspecialchars($proxy_url, ENT_NOQUOTES, 'UTF-8');
    return "<a href=\"$escaped_proxy_url\" class=\"coins_url\">Check@UPEI</a>";
  }

  /**
   * Gets values from each field.
   */
  private function getFieldValues($node, $field_name) {
    $values = [];
    if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
      foreach ($node->get($field_name) as $item) {
        if (isset($item->target_id)) {
          $field_value = $this->entityTypeManager->getStorage('taxonomy_term')->load($item->target_id)->name->value;
        }
        elseif (isset($item->value)) {
          $field_value = $item->value;
        }
        $values[] = $field_value;
      }
      return implode(';', $values);
    }
    return NULL;

  }

  /**
   * Loads and returns node from supplied nid.
   */
  public function nodeFromNid($nid) {
    return $this->entityTypeManager->getStorage('node')->load($nid);
  }

}
