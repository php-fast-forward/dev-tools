<?php

namespace FastForward\DevTools\Composer\Json\Schema;

/**
 * Represents the optional "support" section of a composer.json file.
 *
 * This value object provides structured access to support-related metadata such as
 * support email, issue tracker, forum, wiki, IRC, source code, documentation,
 * RSS feed, chat channel, and security disclosure policy.
 *
 * All properties of this class are immutable after instantiation. Consumers MAY
 * safely treat instances of this class as readonly value objects. Since the
 * "support" section is optional in composer.json, each field MAY contain an empty
 * string when the corresponding value is not defined.
 *
 * Implementations SHALL preserve the exact values provided at construction time.
 * URL-based properties SHOULD contain fully qualified URLs whenever available.
 * The IRC property SHOULD follow the format "irc://server/channel" when provided.
 */
final readonly class Support implements SupportInterface
{
    /**
     * Constructs a new ComposerJsonSupport instance.
     *
     * Each argument represents an optional support entry from the composer.json
     * "support" section. Callers MAY omit any argument, in which case the value
     * SHALL default to an empty string.
     *
     * @param string $email The support email address. SHOULD be a valid email address.
     * @param string $issues The URL to the issue tracker. SHOULD be a fully qualified URL.
     * @param string $forum The URL to the support forum. SHOULD be a fully qualified URL.
     * @param string $wiki The URL to the project wiki. SHOULD be a fully qualified URL.
     * @param string $irc The IRC support channel. SHOULD follow the format "irc://server/channel".
     * @param string $source The URL to browse or download the project source code.
     *                       SHOULD be a fully qualified URL.
     * @param string $docs The URL to the project documentation. SHOULD be a fully qualified URL.
     * @param string $rss The URL to the RSS feed. SHOULD be a fully qualified URL.
     * @param string $chat The URL to the chat channel. SHOULD be a fully qualified URL.
     * @param string $security The URL to the vulnerability disclosure policy.
     *                         SHOULD be a fully qualified URL.
     */
    public function __construct(
        private string $email = '',
        private string $issues = '',
        private string $forum = '',
        private string $wiki = '',
        private string $irc = '',
        private string $source = '',
        private string $docs = '',
        private string $rss = '',
        private string $chat = '',
        private string $security = '',
    ) {
    }

    /**
     * Retrieves the support email address.
     *
     * This method MUST return the support email value exactly as stored by the instance.
     *
     * @return string The support email address.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Retrieves the issue tracker URL.
     *
     * This method MUST return the issue tracker value exactly as stored by the instance.
     *
     * @return string The issue tracker URL.
     */
    public function getIssues(): string
    {
        return $this->issues;
    }

    /**
     * Retrieves the forum URL.
     *
     * This method MUST return the forum value exactly as stored by the instance.
     *
     * @return string The forum URL.
     */
    public function getForum(): string
    {
        return $this->forum;
    }

    /**
     * Retrieves the wiki URL.
     *
     * This method MUST return the wiki value exactly as stored by the instance.
     *
     * @return string The wiki URL.
     */
    public function getWiki(): string
    {
        return $this->wiki;
    }

    /**
     * Retrieves the IRC support channel.
     *
     * This method MUST return the IRC value exactly as stored by the instance.
     *
     * @return string The IRC support channel.
     */
    public function getIrc(): string
    {
        return $this->irc;
    }

    /**
     * Retrieves the source code URL.
     *
     * This method MUST return the source URL value exactly as stored by the instance.
     *
     * @return string The source code URL.
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Retrieves the documentation URL.
     *
     * This method MUST return the documentation URL value exactly as stored by the instance.
     *
     * @return string The documentation URL.
     */
    public function getDocs(): string
    {
        return $this->docs;
    }

    /**
     * Retrieves the RSS feed URL.
     *
     * This method MUST return the RSS feed URL value exactly as stored by the instance.
     *
     * @return string The RSS feed URL.
     */
    public function getRss(): string
    {
        return $this->rss;
    }

    /**
     * Retrieves the chat channel URL.
     *
     * This method MUST return the chat channel URL value exactly as stored by the instance.
     *
     * @return string The chat channel URL.
     */
    public function getChat(): string
    {
        return $this->chat;
    }

    /**
     * Retrieves the vulnerability disclosure policy URL.
     *
     * This method MUST return the security policy URL value exactly as stored by the instance.
     *
     * @return string The vulnerability disclosure policy URL.
     */
    public function getSecurity(): string
    {
        return $this->security;
    }
}