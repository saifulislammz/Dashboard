<?php

declare(strict_types=1);

namespace App\Services\Meetings;

use App\Contracts\MeetingProviderInterface;
use App\Integrations\Google\GoogleMeetProvider;
use App\Integrations\Zoom\ZoomProvider;
use App\Repositories\ProviderAccountRepository;
use InvalidArgumentException;
use PDO;

/**
 * MeetingProviderFactory
 *
 * Resolves and returns the correct provider implementation
 * based on the provider slug string.
 * Keeps instantiation logic centralised and provider-agnostic.
 */
class MeetingProviderFactory
{
    private PDO $db;
    private ProviderAccountRepository $providerRepo;

    public function __construct(PDO $db, ProviderAccountRepository $providerRepo)
    {
        $this->db           = $db;
        $this->providerRepo = $providerRepo;
    }

    /**
     * @param  string $provider  'google_meet' | 'zoom'
     * @return MeetingProviderInterface
     * @throws InvalidArgumentException if unknown provider
     * @throws \RuntimeException if provider not connected
     */
    public function make(string $provider): MeetingProviderInterface
    {
        $account = $this->providerRepo->findByProvider($provider);

        if (!$account || !$account['is_connected']) {
            throw new \RuntimeException(
                "Provider '{$provider}' is not connected. Please configure it in Meeting Settings."
            );
        }

        return match ($provider) {
            'google_meet' => new GoogleMeetProvider($this->db, $account),
            'zoom'        => new ZoomProvider($this->db, $account),
            default       => throw new InvalidArgumentException("Unknown meeting provider: {$provider}"),
        };
    }

    /**
     * Get all available (connected) providers
     */
    public function getConnectedProviders(): array
    {
        return $this->providerRepo->getConnectedProviders();
    }

    /**
     * List of all supported provider slugs
     */
    public static function supportedProviders(): array
    {
        return ['google_meet', 'zoom'];
    }
}
