<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

/**
 * Middleware that specifies which proxies should be trusted by the application.
 * 
 * This is critical for the Documents View feature to correctly determine client
 * IP addresses and other request information when operating behind load balancers,
 * reverse proxies, or CDNs in our hybrid infrastructure environment.
 * 
 * When correctly configured, this middleware ensures:
 * - Proper client IP identification for security logging and audit trails
 * - Correct protocol detection (HTTP/HTTPS) for secure redirects
 * - Accurate host and port information for routing
 */
class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Can be set to one of the following:
     * - A specific IP address: '192.168.1.1'
     * - An array of IP addresses: ['192.168.1.1', '192.168.1.2']
     * - An IP address with wildcard: '192.168.1.*'
     * - A CIDR notation: '192.168.1.0/24'
     * - '*' to trust all proxies (use only in specific environments)
     * - null to trust none
     *
     * @var string|array|null
     */
    protected $proxies = [
        // On-premises load balancers and NGINX Ingress
        '10.0.0.0/8',      // Internal network range
        '172.16.0.0/12',   // Internal network range
        '192.168.0.0/16',  // Internal network range
        
        // AWS services (CloudFront, ELB, etc.)
        // AWS CloudFront IP ranges
        '54.182.0.0/16',
        '54.192.0.0/12',
        '99.84.0.0/16',
        '205.251.192.0/19',
        
        // Uncomment for staging/dev environments to trust all proxies
        // '*',
    ];

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers = 
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}