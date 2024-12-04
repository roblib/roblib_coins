<?php

declare(strict_types=1);

namespace Drupal\roblib_coins\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\roblib_coins\Service\RoblibCoinsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a coin url block.
 *
 * @Block(
 *   id = "roblib_coins_coin_url",
 *   admin_label = @Translation("Coin URL"),
 *   category = @Translation("Custom"),
 * )
 */
final class CoinUrlBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly RoblibCoinsService $roblibCoinsMintCoinService,
    private readonly RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('roblib_coins.mint_coin_service'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node = $this->routeMatch->getParameter('node');
    $markup = $this->roblibCoinsMintCoinService->mintCoin($node);
    $build['content'] = [
      '#markup' => "<span class = 'coin-url'>$markup</span>",
      '#attributes' => [
        'class' => ['coin_url'],
      ],
    ];
    $build['#cache'] = [
      'max-age' => 0,
    ];
    return $build;
  }

}
