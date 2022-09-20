<?php

/**
 * Caches.
 *
 * @package   local_openlms
 * @copyright 2022 Open LMS (https://www.openlms.net/)
 * @author    Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$definitions = array(
    // Hook callbacks cache.
    // There is a static cache in hook manager, data is fetched once per page on first hook execution.
    // This cache needs to be invalidated during upgrades when code changes and when callbacks
    // overrides are updated.
    'hookcallbacks' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => false,
        // WARNING: Manual cache purge may be required when overriding hook callbacks.
        'canuselocalstore' => true,
    ),
);
