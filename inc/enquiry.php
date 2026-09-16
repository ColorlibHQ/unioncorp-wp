<?php
/**
 * The enquiry panel.
 *
 * A restaurant theme lives or dies on this form, so Unioncorp ships one rather than
 * requiring a plugin for the single thing every visitor came to do.
 *
 * It is a **shortcode**, not inline PHP in the pattern. That is not a style
 * preference: inc/front-page-setup.php expands patterns into real post content
 * so the copy stays editable, and PHP inside stored post content never runs.
 * A pattern that rendered the form inline would freeze whatever it produced at
 * activation into the page forever.
 *
 * A theme must not create database tables or register a post type, so Unioncorp
 * stores nothing. It validates, then hands the enquiry to whoever wants it:
 *
 *   - `unioncorp_enquiry_handlers` — return true from any handler to say the
 *     enquiry has been dealt with, and the built-in email is skipped. This is
 *     where a enquiry plugin, a CRM or a webhook hooks in.
 *   - `unioncorp_enquiry_email_to` / `_subject` / `_body` — adjust the email
 *     the theme sends when nothing else claims the enquiry.
 *   - `unioncorp_enquiry_fields` — add, remove or relabel fields.
 *
 * The form works with JavaScript off: it is a plain POST to the same URL,
 * answered with a redirect carrying the result. Nothing here depends on the
 * Interactivity API or on a bundler.
 *
 * @package Unioncorp
 */

defined( 'ABSPATH' ) || exit;

const UNIONCORP_RESERVATION_ACTION = 'unioncorp_enquiry';

/**
 * The fields the form asks for.
 *
 * @return array<string, array<string, mixed>>
 */
function unioncorp_enquiry_fields() {
	$fields = array(
		'name'    => array(
			'label'        => __( 'Your name', 'unioncorp' ),
			'type'         => 'text',
			'autocomplete' => 'name',
			'required'     => true,
		),
		'email'   => array(
			'label'        => __( 'Email address', 'unioncorp' ),
			'type'         => 'email',
			'autocomplete' => 'email',
			'required'     => true,
		),
		'phone'   => array(
			'label'        => __( 'Phone number', 'unioncorp' ),
			'type'         => 'tel',
			'autocomplete' => 'tel',
			'required'     => false,
		),
		'company' => array(
			'label'        => __( 'Company', 'unioncorp' ),
			'type'         => 'text',
			'autocomplete' => 'organization',
			'required'     => false,
		),
		'message' => array(
			'label'    => __( 'How can we help?', 'unioncorp' ),
			'type'     => 'textarea',
			'required' => true,
		),
	);

	/**
	 * Filters the enquiry form fields.
	 *
	 * @param array $fields Field definitions keyed by name.
	 */
	return apply_filters( 'unioncorp_enquiry_fields', $fields );
}

/**
 * Render one field, label included.
 *
 * Labels are real <label for> elements, always. A placeholder is not a label:
 * it is unreadable to some screen readers and it disappears the moment the
 * field has content.
 *
 * @param string $name  Field name.
 * @param array  $field Field definition.
 * @param array  $sent  Previously submitted values, to repopulate on error.
 * @return string
 */
function unioncorp_enquiry_field( $name, $field, $sent = array() ) {
	$id       = 'unioncorp-enquiry-' . $name;
	$value    = isset( $sent[ $name ] ) ? $sent[ $name ] : '';
	$required = ! empty( $field['required'] );

	$attributes = array(
		'id'    => $id,
		'name'  => $name,
		'class' => 'unioncorp-field__control',
	);

	if ( $required ) {
		$attributes['required'] = 'required';
	}
	if ( ! empty( $field['autocomplete'] ) ) {
		$attributes['autocomplete'] = $field['autocomplete'];
	}
	if ( isset( $field['min'] ) ) {
		$attributes['min'] = $field['min'];
	}
	if ( isset( $field['max'] ) ) {
		$attributes['max'] = $field['max'];
	}

	$out = '<p class="unioncorp-field unioncorp-field--' . esc_attr( $name ) . '">';
	$out .= '<label class="unioncorp-field__label" for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] );
	if ( $required ) {
		$out .= ' <span class="unioncorp-field__required" aria-hidden="true">*</span>';
	}
	$out .= '</label>';

	if ( 'textarea' === $field['type'] ) {
		$attributes['rows'] = 4;
		$out               .= '<textarea' . unioncorp_attributes( $attributes ) . '>' . esc_textarea( $value ) . '</textarea>';
	} else {
		$attributes['type']  = $field['type'];
		$attributes['value'] = $value;
		$out                .= '<input' . unioncorp_attributes( $attributes ) . '>';
	}

	return $out . '</p>';
}

/**
 * Build an attribute string from a map, escaping every value.
 *
 * @param array $attributes Attribute map.
 * @return string
 */
function unioncorp_attributes( $attributes ) {
	$out = '';
	foreach ( $attributes as $key => $value ) {
		if ( '' === $value && 'value' !== $key ) {
			continue;
		}
		$out .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
	}
	return $out;
}

/**
 * The enquiry form.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function unioncorp_enquiry_form( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'button' => __( 'Request a consultation', 'unioncorp' ),
		),
		$atts,
		'unioncorp_enquiry_form'
	);

	$notice = unioncorp_enquiry_notice();
	$sent   = array();

	$out  = '<form class="unioncorp-enquiry" method="post" action="' . esc_url( unioncorp_current_url() ) . '#unioncorp-enquiry">';
	$out .= '<div id="unioncorp-enquiry" class="unioncorp-enquiry__anchor"></div>';
	$out .= $notice;
	$out .= wp_nonce_field( UNIONCORP_RESERVATION_ACTION, 'unioncorp_enquiry_nonce', true, false );
	$out .= '<input type="hidden" name="action" value="' . esc_attr( UNIONCORP_RESERVATION_ACTION ) . '">';

	// The page to come back to, carried explicitly.
	//
	// wp_get_referer() cannot do this job: it returns false whenever the
	// referer matches the current request URI, which is always the case for a
	// form that posts to its own page. Falling back to home_url() then dumps
	// the guest on the front page with a enquiry confirmation and no form in
	// sight. Validated with wp_validate_redirect() on the way back out, so a
	// crafted value cannot send anyone off-site.
	$out .= '<input type="hidden" name="unioncorp_redirect" value="' . esc_url( unioncorp_current_url() ) . '">';

	// A field no visitor sees and no visitor fills in. Bots fill everything.
	$out .= '<p class="unioncorp-enquiry__trap" aria-hidden="true">';
	$out .= '<label for="unioncorp-enquiry-website">' . esc_html__( 'Leave this field empty', 'unioncorp' ) . '</label>';
	$out .= '<input id="unioncorp-enquiry-website" type="text" name="unioncorp_website" tabindex="-1" autocomplete="off">';
	$out .= '</p>';

	$out .= '<div class="unioncorp-enquiry__grid">';
	foreach ( unioncorp_enquiry_fields() as $name => $field ) {
		$out .= unioncorp_enquiry_field( $name, $field, $sent );
	}
	$out .= '</div>';

	$out .= '<p class="unioncorp-enquiry__actions">';
	$out .= '<button type="submit" class="wp-block-button__link wp-element-button">' . esc_html( $atts['button'] ) . '</button>';
	$out .= '</p>';

	$out .= '</form>';

	return $out;
}
add_shortcode( 'unioncorp_enquiry_form', 'unioncorp_enquiry_form' );

/**
 * The current URL, without any previous result parameter.
 *
 * @return string
 */
function unioncorp_current_url() {
	$permalink = get_permalink();
	if ( ! $permalink ) {
		$permalink = home_url( '/' );
	}
	return remove_query_arg( array( 'unioncorp-enquiry' ), $permalink );
}

/**
 * The message shown after a submission, if there is one.
 *
 * @return string
 */
function unioncorp_enquiry_notice() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$result = isset( $_GET['unioncorp-enquiry'] ) ? sanitize_key( wp_unslash( $_GET['unioncorp-enquiry'] ) ) : '';

	$messages = array(
		'sent'    => array( 'ok', __( 'Thank you — your enquiry is with us. We will reply by email shortly.', 'unioncorp' ) ),
		'invalid' => array( 'error', __( 'Please check the form: we still need a name, an email address, a date, a time and how many of you there are.', 'unioncorp' ) ),
		'email'   => array( 'error', __( 'That email address does not look right.', 'unioncorp' ) ),
		'failed'  => array( 'error', __( 'Sorry, the enquiry could not be sent. Please email or call us instead.', 'unioncorp' ) ),
		'expired' => array( 'error', __( 'That form had been open a while and expired. Please send it again.', 'unioncorp' ) ),
	);

	if ( ! isset( $messages[ $result ] ) ) {
		return '';
	}

	list( $kind, $text ) = $messages[ $result ];

	return '<p class="unioncorp-enquiry__notice is-' . esc_attr( $kind ) . '" role="status">' . esc_html( $text ) . '</p>';
}

/**
 * Handle a submitted enquiry.
 *
 * Runs on `template_redirect` so it can redirect before anything is sent —
 * the POST/redirect/GET that stops a refresh re-enquiry the table.
 */
function unioncorp_handle_enquiry() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked immediately below.
	if ( ! isset( $_POST['action'] ) || UNIONCORP_RESERVATION_ACTION !== $_POST['action'] ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked below; this only selects where to redirect.
	$posted   = isset( $_POST['unioncorp_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['unioncorp_redirect'] ) ) : '';
	$redirect = wp_validate_redirect( $posted, home_url( '/' ) );
	$redirect = remove_query_arg( array( 'unioncorp-enquiry' ), $redirect );

	$nonce = isset( $_POST['unioncorp_enquiry_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['unioncorp_enquiry_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, UNIONCORP_RESERVATION_ACTION ) ) {
		unioncorp_enquiry_redirect( $redirect, 'expired' );
	}

	// Silently accept and discard anything that filled the honeypot: telling a
	// bot it failed only teaches it to try again differently.
	if ( ! empty( $_POST['unioncorp_website'] ) ) {
		unioncorp_enquiry_redirect( $redirect, 'sent' );
	}

	$enquiry = array();
	foreach ( unioncorp_enquiry_fields() as $name => $field ) {
		$raw = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
		$raw = is_string( $raw ) ? $raw : '';

		if ( 'textarea' === $field['type'] ) {
			$value = sanitize_textarea_field( $raw );
		} elseif ( 'email' === $field['type'] ) {
			$value = sanitize_email( $raw );
		} else {
			$value = sanitize_text_field( $raw );
		}

		if ( ! empty( $field['required'] ) && '' === $value ) {
			unioncorp_enquiry_redirect( $redirect, 'invalid' );
		}

		$enquiry[ $name ] = $value;
	}

	if ( ! empty( $enquiry['email'] ) && ! is_email( $enquiry['email'] ) ) {
		unioncorp_enquiry_redirect( $redirect, 'email' );
	}

	/**
	 * Filters whether the enquiry has already been handled.
	 *
	 * Return true from any handler and Unioncorp will not send its own email —
	 * which is how a enquiry plugin, a CRM or a webhook takes this over.
	 *
	 * @param bool  $handled Whether something has dealt with the enquiry.
	 * @param array $enquiry The sanitised enquiry.
	 */
	$handled = apply_filters( 'unioncorp_enquiry_handlers', false, $enquiry );

	if ( ! $handled ) {
		$handled = unioncorp_enquiry_email( $enquiry );
	}

	unioncorp_enquiry_redirect( $redirect, $handled ? 'sent' : 'failed' );
}
add_action( 'template_redirect', 'unioncorp_handle_enquiry' );

/**
 * Redirect back to the form with a result, and stop.
 *
 * @param string $url    Where to go.
 * @param string $result Result key.
 */
function unioncorp_enquiry_redirect( $url, $result ) {
	wp_safe_redirect( add_query_arg( 'unioncorp-enquiry', $result, $url ) . '#unioncorp-enquiry', 303 );
	exit;
}

/**
 * Email the enquiry to the site's admin address.
 *
 * From: is the site's own address, never the visitor's. Putting the visitor
 * there fails SPF and DMARC — the mail is sent by this server, not by their
 * provider — and it is the classic route to header injection. Reply-To carries
 * them instead, and wp_mail() rejects a header containing a newline.
 *
 * @param array $enquiry Sanitised enquiry.
 * @return bool
 */
function unioncorp_enquiry_email( $enquiry ) {
	$to = apply_filters( 'unioncorp_enquiry_email_to', get_option( 'admin_email' ) );

	if ( ! $to || ! is_email( $to ) ) {
		return false;
	}

	/* translators: %s: site name. */
	$subject = sprintf( __( '[%s] Website enquiry', 'unioncorp' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$subject = apply_filters( 'unioncorp_enquiry_email_subject', $subject, $enquiry );

	$lines  = array();
	$fields = unioncorp_enquiry_fields();
	foreach ( $enquiry as $name => $value ) {
		if ( '' === $value ) {
			continue;
		}
		$label   = isset( $fields[ $name ]['label'] ) ? $fields[ $name ]['label'] : $name;
		$lines[] = $label . ': ' . $value;
	}

	$body = implode( "\n", $lines );
	$body = apply_filters( 'unioncorp_enquiry_email_body', $body, $enquiry );

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( ! empty( $enquiry['email'] ) && is_email( $enquiry['email'] ) ) {
		$headers[] = 'Reply-To: ' . $enquiry['email'];
	}

	return (bool) wp_mail( $to, $subject, $body, $headers );
}
