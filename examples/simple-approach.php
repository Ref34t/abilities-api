<?php
/**
 * WP Restaurant Manager - Simple Approach
 *
 * This file demonstrates the "Simple Approach" for using the Abilities API.
 * Each ability is registered individually using `wp_register_ability()`.
 *
 * @package WP_Restaurant_Manager
 */

namespace WP_Restaurant_Manager\Simple;

/**
 * Registers all restaurant-related abilities.
 *
 * NOTE: This function demonstrates the verbosity and duplication of the simple approach.
 * Each ability registration is a separate, lengthy block of code.
 * The permission callbacks are often duplicated across multiple abilities.
 */
function register_abilities() {

	// NOTE: Each `wp_register_ability` call is a standalone registration.
	// There is no easy way to share logic (like logging, rate-limiting, or subscription checks)
	// between them without calling a separate function from each `execute_callback`.

	// Ability 1: Generate Menu Description.
	wp_register_ability(
		'wp-restaurant/generate-menu-description',
		array(
			'label'               => __( 'Generate Menu Description', 'wp-restaurant' ),
			'description'         => __( 'Generate an appetizing description for a menu item using AI', 'wp-restaurant' ),
			'execute_callback'    => __NAMESPACE__ . '\execute_generate_description',
			'permission_callback' => __NAMESPACE__ . '\can_manage_menu', // Duplicated logic.
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'dish_name'   => array( 'type' => 'string' ),
					'ingredients' => array( 'type' => 'array' ),
					'cuisine'     => array( 'type' => 'string' ),
				),
				'required'   => array( 'dish_name', 'ingredients' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'description' => array( 'type' => 'string' ),
					'length'      => array( 'type' => 'integer' ),
				),
			),
		)
	);

	// Ability 2: Suggest Wine Pairings.
	wp_register_ability(
		'wp-restaurant/suggest-wine-pairing',
		array(
			'label'               => __( 'Suggest Wine Pairing', 'wp-restaurant' ),
			'description'         => __( 'Suggest wine pairings for a specific dish', 'wp-restaurant' ),
			'execute_callback'    => __NAMESPACE__ . '\execute_wine_pairing',
			'permission_callback' => __NAMESPACE__ . '\can_manage_menu', // Duplicated logic.
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'dish_name'    => array( 'type' => 'string' ),
					'main_protein' => array( 'type' => 'string' ),
					'sauce_type'   => array( 'type' => 'string' ),
				),
				'required'   => array( 'dish_name' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'suggestions' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'wine_name' => array( 'type' => 'string' ),
								'reason'    => array( 'type' => 'string' ),
							),
						),
					),
				),
			),
		)
	);

	// Ability 3: Create Dietary Alternatives.
	wp_register_ability(
		'wp-restaurant/dietary-alternatives',
		array(
			'label'               => __( 'Create Dietary Alternatives', 'wp-restaurant' ),
			'description'         => __( 'Suggest ingredient substitutions for dietary restrictions', 'wp-restaurant' ),
			'execute_callback'    => __NAMESPACE__ . '\execute_dietary_alternatives',
			'permission_callback' => __NAMESPACE__ . '\can_manage_menu', // Duplicated logic.
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'dish_name'   => array( 'type' => 'string' ),
					'ingredients' => array( 'type' => 'array' ),
					'restriction' => array(
						'type' => 'string',
						'enum' => array( 'vegan', 'gluten-free', 'dairy-free', 'nut-free' ),
					),
				),
				'required'   => array( 'dish_name', 'ingredients', 'restriction' ),
			),
		)
	);

	// Ability 4: Generate Social Media Post.
	wp_register_ability(
		'wp-restaurant/generate-social-post',
		array(
			'label'               => __( 'Generate Social Media Post', 'wp-restaurant' ),
			'description'         => __( 'Create promotional social media content for menu items', 'wp-restaurant' ),
			'execute_callback'    => __NAMESPACE__ . '\execute_social_post',
			'permission_callback' => __NAMESPACE__ . '\can_manage_marketing',
			'input_schema'        => array(
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
			),
		)
	);

	// Ability 5: Analyze Customer Reviews.
	wp_register_ability(
		'wp-restaurant/analyze-reviews',
		array(
			'label'               => __( 'Analyze Customer Reviews', 'wp-restaurant' ),
			'description'         => __( 'Extract insights and sentiment from customer feedback', 'wp-restaurant' ),
			'execute_callback'    => __NAMESPACE__ . '\execute_analyze_reviews',
			'permission_callback' => __NAMESPACE__ . '\can_view_analytics',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'reviews'     => array( 'type' => 'array' ),
					'time_period' => array( 'type' => 'string' ),
				),
				'required'   => array( 'reviews' ),
			),
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\register_abilities' );

// --- Execute Callbacks ---

/**
 * Executes the 'generate-menu-description' ability.
 *
 * NOTE: In this approach, any cross-cutting concern like logging, rate-limiting,
 * or subscription checks would need to be manually added to this function and
 * every other `execute_` function, leading to significant code duplication.
 *
 * @param array $input The input data.
 * @return array|\WP_Error The result or an error.
 */
function execute_generate_description( $input ) {
	// 1. Manual validation.
	if ( empty( $input['dish_name'] ) || empty( $input['ingredients'] ) ) {
		return new \WP_Error( 'missing_data', 'Dish name and ingredients are required' );
	}

	// 2. AI Prompt Generation.
	$prompt = sprintf(
		'Write an appetizing menu description for "%s". Ingredients: %s. %s',
		$input['dish_name'],
		implode( ', ', $input['ingredients'] ),
		! empty( $input['cuisine'] ) ? 'Cuisine style: ' . $input['cuisine'] : ''
	);

	// 3. AI Service Call (mocked).
	$description = "A delicious {$input['dish_name']} featuring " . implode( ', ', $input['ingredients'] );

	// 4. Return result.
	return array(
		'description' => $description,
		'length'      => strlen( $description ),
	);
}

/**
 * Executes the 'suggest-wine-pairing' ability.
 * NOTE: Duplication of logic would be required here for shared concerns.
 */
function execute_wine_pairing( $input ) {
	if ( empty( $input['dish_name'] ) ) {
		return new \WP_Error( 'missing_data', 'Dish name is required' );
	}
	// AI call would happen here.
	return array(
		'suggestions' => array(
			array(
				'wine_name' => 'Pinot Noir',
				'reason'    => 'Complements the rich flavors',
			),
		),
	);
}

/**
 * Executes the 'dietary-alternatives' ability.
 * NOTE: Duplication of logic would be required here for shared concerns.
 */
function execute_dietary_alternatives( $input ) {
	$required = array( 'dish_name', 'ingredients', 'restriction' );
	foreach ( $required as $field ) {
		if ( empty( $input[ $field ] ) ) {
			return new \WP_Error( 'missing_data', "{$field} is required" );
		}
	}
	// AI processing.
	return array(
		'alternatives' => array(),
		'original'     => $input['ingredients'],
	);
}

/**
 * Executes the 'generate-social-post' ability.
 * NOTE: Duplication of logic would be required here for shared concerns.
 */
function execute_social_post( $input ) {
	if ( empty( $input['dish_name'] ) || empty( $input['platform'] ) ) {
		return new \WP_Error( 'missing_data', 'Dish name and platform are required' );
	}
	// Generate platform-specific post.
	return array(
		'post' => "Check out our amazing {$input['dish_name']}! 🍽️",
	);
}

/**
 * Executes the 'analyze-reviews' ability.
 * NOTE: Duplication of logic would be required here for shared concerns.
 */
function execute_analyze_reviews( $input ) {
	if ( empty( $input['reviews'] ) ) {
		return new \WP_Error( 'missing_data', 'Reviews are required' );
	}
	return array(
		'sentiment' => 'positive',
		'themes'    => array(),
	);
}


// --- Permission Callbacks ---

/**
 * Checks if the current user can manage the menu.
 *
 * NOTE: This same function is referenced by three different abilities.
 * If the logic needed to be slightly different for each, we would need three
 * separate but very similar functions, leading to more duplication.
 *
 * @return bool True if the user can edit posts, false otherwise.
 */
function can_manage_menu() {
	return current_user_can( 'edit_posts' );
}

/**
 * Checks if the current user can manage marketing.
 *
 * @return bool True if the user can edit posts, false otherwise.
 */
function can_manage_marketing() {
	return current_user_can( 'edit_posts' );
}

/**
 * Checks if the current user can view analytics.
 *
 * @return bool True if the user can edit posts, false otherwise.
 */
function can_view_analytics() {
	return current_user_can( 'edit_posts' );
}
