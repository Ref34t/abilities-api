<?php
/**
 * WP_Ability_Category class.
 *
 * @package    WordPress
 * @subpackage Abilities_API
 * @since      S.I.N.C.E
 */

declare( strict_types = 1 );

/**
 * Core class representing an ability category.
 *
 * @since S.I.N.C.E
 */
final class WP_Ability_Category {

    /**
     * The category's unique identifier.
     *
     * @since S.I.N.C.E
     * @var string
     */
    public $id;

    /**
     * The human-readable label for the category.
     *
     * @since S.I.N.C.E
     * @var string
     */
    public $label;

    /**
     * A description of the category.
     *
     * @since S.I.N.C.E
     * @var string
     */
    public $description;

    /**
     * Constructor.
     *
     * @since S.I.N.C.E
     *
     * @param string $id   The category ID.
     * @param array  $args The arguments for the category.
     */
    public function __construct( string $id, array $args ) {
        $this->id          = $id;
        $this->label       = $args['label'] ?? '';
        $this->description = $args['description'] ?? '';
    }
}
