<?php

namespace FastForward\DevTools\Composer\Json\Schema;

/**
 * Defines the contract for representing the "support" section of a composer.json file.
 *
 * The support section contains optional metadata intended to help users, contributors,
 * and integrators obtain assistance, report issues, review documentation, access source
 * code, and follow project communication channels.
 *
 * Implementations of this interface MUST provide string-based accessors for each supported
 * support entry defined by Composer metadata conventions. Since the "support" section is
 * optional, implementations MAY return an empty string when a given support entry is not
 * defined. Implementations SHOULD return normalized and human-usable values whenever possible.
 *
 * URL-based values SHOULD be fully qualified. The IRC value SHOULD follow the format
 * "irc://server/channel" when provided. The email value SHOULD represent a valid support
 * email address when available.
 *
 * The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT",
 * "SHOULD", "SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL" in this
 * interface are to be interpreted as described in RFC 2119.
 */
interface SupportInterface
{
    /**
     * Retrieves the support email address.
     *
     * This method MUST return the email address intended for support requests.
     * Implementations MAY return an empty string when no support email is defined.
     * Implementations SHOULD return a syntactically valid email address.
     *
     * @return string The support email address.
     */
    public function getEmail(): string;

    /**
     * Retrieves the URL to the issue tracker.
     *
     * This method MUST return the URL used to report bugs, request features,
     * or otherwise track issues related to the project.
     * Implementations MAY return an empty string when no issue tracker is defined.
     * Implementations SHOULD return a fully qualified URL.
     *
     * @return string The issue tracker URL.
     */
    public function getIssues(): string;

    /**
     * Retrieves the URL to the support forum.
     *
     * This method MUST return the URL of the forum intended for community support
     * or project-related discussions.
     * Implementations MAY return an empty string when no forum is defined.
     * Implementations SHOULD return a fully qualified URL.
     *
     * @return string The forum URL.
     */
    public function getForum(): string;

    /**
     * Retrieves the URL to the project wiki.
     *
     * This method MUST return the URL of the wiki that provides project knowledge,
     * guides, or collaborative documentation.
     * Implementations MAY return an empty string when no wiki is defined.
     * Implementations SHOULD return a fully qualified URL.
     *
     * @return string The wiki URL.
     */
    public function getWiki(): string;

    /**
     * Retrieves the IRC channel for support.
     *
     * This method MUST return the IRC endpoint intended for project support.
     * Implementations MAY return an empty string when no IRC channel is defined.
     * Implementations SHOULD return the value in the format "irc://server/channel".
     *
     * @return string The IRC support channel.
     */
    public function getIrc(): string;

    /**
     * Retrieves the URL to browse or download the project source code.
     *
     * This method MUST return the source URL associated with the project.
     * Implementations MAY return an empty string when no source URL is defined.
     * Implementations SHOULD return a fully qualified URL.
     *
     * @return string The source code URL.
     */
    public function getSource(): string;

    /**
     * Retrieves the URL to the project documentation.
     *
     * This method MUST return the URL that points to official documentation
     * or usage guides for the project.
     * Implementations MAY return an empty string when no documentation URL is defined.
     * Implementations SHOULD return a fully qualified URL.
     *
     * @return string The documentation URL.
     */
    public function getDocs(): string;

    /**
     * Retrieves the URL to the RSS feed.
     *
     * This method MUST return the RSS feed URL when the project provides syndicated updates.
     * Implementations MAY return an empty string when no RSS feed is defined.
     * Implementations SHOULD return a fully qualified URL.
     *
     * @return string The RSS feed URL.
     */
    public function getRss(): string;

    /**
     * Retrieves the URL to the chat channel.
     *
     * This method MUST return the URL for the project's chat-based support
     * or communication channel.
     * Implementations MAY return an empty string when no chat channel is defined.
     * Implementations SHOULD return a fully qualified URL.
     *
     * @return string The chat channel URL.
     */
    public function getChat(): string;

    /**
     * Retrieves the URL to the vulnerability disclosure policy.
     *
     * This method MUST return the URL that describes how security vulnerabilities
     * SHALL be reported to the project maintainers.
     * Implementations MAY return an empty string when no security policy is defined.
     * Implementations SHOULD return a fully qualified URL.
     *
     * @return string The vulnerability disclosure policy URL.
     */
    public function getSecurity(): string;
}