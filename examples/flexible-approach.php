<?php
/**
 * WP Restaurant Manager - Flexible Approach
 *
 * This file demonstrates the "Flexible Approach" using an abstract base class for abilities.
 * This object-oriented pattern allows for shared logic (permissions, rate-limiting, logging)
 * to be defined once and inherited by all abilities, adhering to DRY principles.
 *
 * @package WP_Restaurant_Manager
 */

namespace WP_Restaurant_Manager\Flexible;

/**
 * Abstract base class for all restaurant abilities.
 *
 * This class contains shared logic that ALL restaurant abilities need.
 * By defining shared permission checks, pre-execution hooks (logging, rate-limiting),
 * and post-execution hooks here, we avoid duplicating code in every ability.
 */
abstract class Restaurant_Ability extends \WP_Ability {

	/**
	 * SHARED PERMISSION LOGIC
	 *
	 * Defines a default permission callback for most restaurant abilities.
	 * This single method includes checks for user roles, restaurant status, and rate limiting.
	 * It is written ONCE and inherited by all child abilities unless they need to override it.
	 */
	protected function get_permission_callback(): callable {
		return function ( $input ) {
			// Check 1: User has the basic role required.
			if ( ! current_user_can( 'edit_posts' ) ) {
				return new \WP_Error( 'permission_denied', 'You do not have permission to manage the restaurant.' );
			}

			// Check 2: Shared business logic (e.g., restaurant is active).
			if ( ! $this->is_restaurant_active() ) {
				return new \WP_Error( 'restaurant_closed', 'Restaurant management is currently disabled.' );
			}

			// Check 3: Shared technical logic (e.g., rate limiting to prevent API abuse).
			if ( ! $this->check_rate_limit() ) {
				return new \WP_Error( 'rate_limit', 'Too many requests. Please wait.' );
			}

			return true;
		};
	}

	/**
	 * SHARED EXECUTION WRAPPER
	 *
	 * This method acts as a wrapper around the specific logic of each ability.
	 * It ensures that shared tasks like logging and subscription checks are ALWAYS run.
	 * This is a powerful pattern for enforcing business rules across all abilities.
	 */
	protected function execute( $input ) {
		// Shared Pre-Execution Hook 1: Log every ability usage for analytics.
		$this->log_ability_usage( $input );

		// Shared Pre-Execution Hook 2: Check subscription status before running expensive AI tasks.
		if ( ! $this->has_active_subscription() ) {
			return new \WP_Error( 'subscription_required', 'An active subscription is required to use this feature.' );
		}

		// Call the specific ability's implementation.
		$result = $this->execute_ability( $input );

		// Shared Post-Execution Hook: Track the result for analytics.
		$this->track_result( $result );

		return $result;
	}

	/**
	 * Each child ability MUST implement its own specific logic in this method.
	 */
	abstract protected function execute_ability( $input );

	// --- Shared Helper Methods (Written Once) ---

	protected function is_restaurant_active(): bool {
		return (bool) get_option( 'wp_restaurant_active', true );
	}

	protected function check_rate_limit(): bool {
		$user_id = get_current_user_id();
		$key     = "restaurant_ability_limit_{$user_id}";
		$count   = get_transient( $key ) ?: 0;

		if ( $count >= 100 ) { // 100 requests per hour.
			return false;
		}

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}

	protected function has_active_subscription(): bool {
		// In a real plugin, this would check with a payment provider like Stripe or Freemius.
		return true;
	}

	protected function log_ability_usage( $input ): void {
		// In a real plugin, this would log to a database or analytics service.
		do_action( 'wp_restaurant_ability_used', $this->get_name(), $input );
	}

	protected function track_result( $result ): void {
		if ( is_wp_error( $result ) ) {
			do_action( 'wp_restaurant_ability_error', $this->get_name(), $result );
		} else {
			do_action( 'wp_restaurant_ability_success', $this->get_name(), $result );
		}
	}
}

/**
 * Ability 1: Generate Menu Description
 *
 * NOTE: This class is very lean. It only defines its unique properties and logic.
 * All the shared logic (permissions, logging, etc.) is inherited from `Restaurant_Ability`.
 */
class Generate_Menu_Description_Ability extends Restaurant_Ability {

	protected function get_label(): string {
		return __( 'Generate Menu Description', 'wp-restaurant' );
	}

	protected function get_description(): string {
		return __( 'Generate an appetizing description for a menu item using AI', 'wp-restaurant' );
	}

	protected function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'dish_name'   => array( 'type' => 'string' ),
				'ingredients' => array( 'type' => 'array' ),
				'cuisine'     => array( 'type' => 'string' ),
			),
			'required'   => array( 'dish_name', 'ingredients' ),
		);
	}

	protected function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'description' => array( 'type' => 'string' ),
				'length'      => array( 'type' => 'integer' ),
			),
		);
	}

	protected function execute_ability( $input ) {
		if ( empty( $input['dish_name'] ) || empty( $input['ingredients'] ) ) {
			return new \WP_Error( 'missing_data', 'Dish name and ingredients are required' );
		}

		$prompt = sprintf(
			'Write an appetizing menu description for "%s". Ingredients: %s. %s',
			$input['dish_name'],
			implode( ', ', $input['ingredients'] ),
			! empty( $input['cuisine'] ) ? 'Cuisine style: ' . $input['cuisine'] : ''
		);

		$description = "A delicious {$input['dish_name']} featuring " . implode( ', ', $input['ingredients'] );

		return array(
			'description' => $description,
			'length'      => strlen( $description ),
		);
	}
}

/**
 * Ability 2: Suggest Wine Pairings
 * NOTE: Another lean class that inherits all shared logic.
 */
class Suggest_Wine_Pairing_Ability extends Restaurant_Ability {

	protected function get_label(): string {
		return __( 'Suggest Wine Pairing', 'wp-restaurant' );
	}

	protected function get_description(): string {
		return __( 'Suggest wine pairings for a specific dish', 'wp-restaurant' );
	}

	protected function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'dish_name'    => array( 'type' => 'string' ),
				'main_protein' => array( 'type' => 'string' ),
				'sauce_type'   => array( 'type' => 'string' ),
			),
			'required'   => array( 'dish_name' ),
		);
	}

	protected function execute_ability( $input ) {
		if ( empty( $input['dish_name'] ) ) {
			return new \WP_Error( 'missing_data', 'Dish name is required' );
		}

		return array(
			'suggestions' => array(
				array(
					'wine_name' => 'Pinot Noir',
					'reason'    => 'Complements the rich flavors',
				),
			),
		);
	}
}

/**
 * Ability 4: Generate Social Media Post
 *
 * NOTE: This class demonstrates the flexibility of the pattern.
 * It overrides the default permission callback with its own specific logic,
 * while still inheriting all the other shared logic (rate-limiting, logging, etc).
 */
class Generate_Social_Post_Ability extends Restaurant_Ability {

	// OVERRIDE: This ability requires a different permission level.
	protected function get_permission_callback(): callable {
		return function ( $input ) {
			// 1. Use a different capability check for marketing.
			if ( ! current_user_can( 'publish_posts' ) ) {
				return new \WP_Error( 'permission_denied', 'You do not have marketing permissions.' );
			}

			// 2. Still use the SHARED helper methods from the base class.
			if ( ! $this->is_restaurant_active() ) {
				return new \WP_Error( 'restaurant_closed', 'Restaurant management is currently disabled.' );
			}
			if ( ! $this->check_rate_limit() ) {
				return new \WP_Error( 'rate_limit', 'Too many requests. Please wait.' );
			}

			return true;
		};
	}

	protected function get_label(): string {
		return __( 'Generate Social Media Post', 'wp-restaurant' );
	}

	protected function get_description(): string {
		return __( 'Create promotional social media content for menu items', 'wp-restaurant' );
	}

	protected function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'dish_name'   => array( 'type' => 'string' ),
				'description' => array( 'type' => 'string' ),
				'platform'    => array(
					'type' => 'string',
					'enum' => array( 'instagram', 'facebook', 'twitter' ),
				),
			),
			'required'   => array( 'dish_name', 'platform' ),
		);
	}

	protected function execute_ability( $input ) {
		if ( empty( $input['dish_name'] ) || empty( $input['platform'] ) ) {
			return new \WP_Error( 'missing_data', 'Dish name and platform are required' );
		}

		return array(
			'post' => "Check out our amazing {$input['dish_name']}! 🍽️",
		);
	}
}

// ... Other ability classes like Dietary_Alternatives_Ability and Analyze_Reviews_Ability would follow the same lean pattern ...

/**
 * Registers all abilities using the flexible, class-based approach.
 *
 * NOTE: With this pattern, registration is clean and declarative.
 * We only pass the `ability_class`. All other details are encapsulated in the class itself.
 * This is the code that would trigger a `PHPStan` error if the PR is merged,
 * as `description`, `execute_callback`, etc., are not provided here.
 * This demonstrates being "punished for following DRY principles."
 */
function register_abilities() {
	wp_register_ability(
		'wp-restaurant/generate-menu-description',
		array(
			'ability_class' => Generate_Menu_Description_Ability::class,
		)
	);

	wp_register_ability(
		'wp-restaurant/suggest-wine-pairing',
		array(
			'ability_class' => Suggest_Wine_Pairing_Ability::class,
		)
	);

	wp_register_ability(
		'wp-restaurant/generate-social-post',
		array(
			'ability_class' => Generate_Social_Post_Ability::class,
		)
	);

	// wp_register_ability( 'wp-restaurant/dietary-alternatives', ... );
	// wp_register_ability( 'wp-restaurant/analyze-reviews', ... );
}
add_action( 'init', __NAMESPACE__ . '\register_abilities' );
