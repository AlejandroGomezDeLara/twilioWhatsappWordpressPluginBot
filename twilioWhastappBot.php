<?php
/**
 * Plugin Name: Twilio WhatsApp Bot (Editable)
 * Description: Bot WhatsApp Twilio con menú 1–8, textos editables desde WP y flujo SI/NO. Endpoint: /wp-json/twilio-bot/v1/whatsapp
 * Version: 2.0.0
 * Author: Alejandro Gómez de Lara Medina
 */

if (!defined('ABSPATH')) exit;

class TWAB_Plugin_Editable {
  // Opciones (config)
  const OPT_AUTH_TOKEN   = 'twab_twilio_auth_token';
  const OPT_PDF_URL      = 'twab_pdf_url';
  const OPT_NOTIFY_EMAIL = 'twab_notify_email';

  // Opciones (textos)
  const OPT_WELCOME_MENU   = 'twab_txt_welcome_menu';
  const OPT_OPT1           = 'twab_txt_opt1';
  const OPT_OPT2           = 'twab_txt_opt2';
  const OPT_OPT3           = 'twab_txt_opt3';
  const OPT_OPT4           = 'twab_txt_opt4';
  const OPT_OPT5_QUESTION  = 'twab_txt_opt5_question';
  const OPT_OPT5_YES       = 'twab_txt_opt5_yes';
  const OPT_OPT5_NO        = 'twab_txt_opt5_no';
  const OPT_OPT6           = 'twab_txt_opt6';
  const OPT_OPT7           = 'twab_txt_opt7';
  const OPT_OPT8_PROMPT    = 'twab_txt_opt8_prompt';

  const OPT_AFTER_HELP_ASK = 'twab_txt_after_help_ask';
  const OPT_AFTER_HELP_YES = 'twab_txt_after_help_yes';
  const OPT_AFTER_HELP_NO  = 'twab_txt_after_help_no';
  const OPT_RECEIVED_FREE  = 'twab_txt_received_free';

  const STATE_TTL = 86400; // 24h

  public function __construct() {
    add_action('rest_api_init', [$this, 'register_routes']);
    add_action('admin_menu', [$this, 'admin_menu']);
    add_action('admin_init', [$this, 'register_settings']);
    register_activation_hook(__FILE__, [$this, 'on_activate']);
  }

  /** ---------------------------
   *  Activación: defaults
   *  --------------------------- */
  public function on_activate() {
    // Config defaults
    if (get_option(self::OPT_NOTIFY_EMAIL, '') === '') {
      update_option(self::OPT_NOTIFY_EMAIL, get_option('admin_email'));
    }

    // Text defaults (solo si no existen)
    $defaults = $this->default_texts();
    foreach ($defaults as $opt => $val) {
      if (get_option($opt, null) === null) update_option($opt, $val);
    }
  }

  private function default_texts() {
    return [
      self::OPT_WELCOME_MENU =>
        "Hola 👋 ¿En qué podemos ayudarte?\n".
        "1.- Me gustaría recibir información de los servicios\n".
        "2.- Me gustaría pedir una primera visita\n".
        "3.- Soy paciente y me gustaría pedir una cita\n".
        "4.- Me gustaría modificar o cancelar una cita\n".
        "5.- Llego tarde a la sesión\n".
        "6.- Problemas para acceder al centro\n".
        "7.- Problemas con el link de videollamada o conexión\n".
        "8.- Otra consulta",

      self::OPT_OPT1 =>
        "Genial, te facilitamos un PDF con toda la información sobre el funcionamiento del centro.\n".
        "Cualquier duda, nos puedes comentar. Gracias",

      self::OPT_OPT2 =>
        "Gracias por confiar en nosotros, te facilitamos un link donde aparece todo nuestro equipo y podrás reservar una primera sesión.\n".
        "Es importante tener en cuenta la información que aparece en cada terapeuta (horario disponible, idiomas de terapia, formato online o presencial y especialidad).\n".
        "Cualquier duda, nos puedes comentar. Gracias\n".
        "Link: https://company.eholo.health/es/centros/centronuriajorba",

      self::OPT_OPT3 =>
        "¿Cómo estás? Te facilitamos el link donde aparece todo nuestro equipo y debes seleccionar a tu terapeuta y elegir el servicio concreto que necesites.\n".
        "Recuerda que el pago se hace de forma automatizada y que debes recibir un mail de confirmación de la cita con todos los datos y si es online el link de videollamada.\n".
        "Si no lo recibes contacta de nuevo con nosotros. Gracias\n".
        "Link: https://company.eholo.health/es/centros/centronuriajorba",

      self::OPT_OPT4 =>
        "Sin problema, para ello debes dirigirte al email de confirmación de cita y en él aparece la opción de cancelación o modificación. Gracias",

      self::OPT_OPT5_QUESTION =>
        "No te preocupes, ¿tienes el mail de tu terapeuta?\nResponde: *SI* / *NO*",

      self::OPT_OPT5_YES =>
        "Te pedimos por favor que le escribas un email comentando a qué hora podrás estar en el centro.\n".
        "Recuerda que la sesión se terminará a la hora marcada y que a partir de los 30 minutos de retraso la sesión se anulará.",

      self::OPT_OPT5_NO =>
        "Entendemos que a veces vamos con un poco de retraso. Pasados 15min la propia terapeuta contactará contigo vía llamada o email.",

      self::OPT_OPT6 =>
        "En tu email de recordatorio de cita encontrarás tu código para acceder al centro.\n".
        "Al lado derecho de la puerta hay un teclado para introducir el código de 6 dígitos.\n".
        "Si aún así no puedes acceder llama al timbre y espera ser atendido.",

      self::OPT_OPT7 =>
        "Recuerda conectarte a través del link recibido en el último correo de recordatorio.\n".
        "Si llevas unos 10min y sigue sin conectarse la terapeuta revisa tu email porque habrás recibido un nuevo correo con el link pertinente.",

      self::OPT_OPT8_PROMPT =>
        "Coméntanos lo que necesites e intentaremos atenderte lo antes posible. Gracias",

      self::OPT_AFTER_HELP_ASK =>
        "¿Hemos resuelto tus dudas? Responde: *SI* / *NO*",

      self::OPT_AFTER_HELP_YES =>
        "Genial, muchas gracias 🙂",

      self::OPT_AFTER_HELP_NO =>
        "Coméntanos qué necesitas e intentaremos atenderte lo antes posible. Gracias",

      self::OPT_RECEIVED_FREE =>
        "Gracias. Hemos recibido tu consulta y te responderemos lo antes posible.",
    ];
  }

  /** ---------------------------
   *  REST endpoint Twilio
   *  --------------------------- */
  public function register_routes() {
    register_rest_route('twilio-bot/v1', '/whatsapp', [
      'methods'             => 'POST',
      'callback'            => [$this, 'handle_incoming'],
      'permission_callback' => '__return_true',
    ]);
  }

  public function handle_incoming(WP_REST_Request $request) {
    // Firma Twilio opcional
    $authToken = trim((string) get_option(self::OPT_AUTH_TOKEN, ''));
    if ($authToken !== '') {
      $signature = $this->get_header($request, 'X-Twilio-Signature');
      if (!$this->validate_twilio_signature($request, $authToken, $signature)) {
        return $this->twiml_response("Error de validación de firma.");
      }
    }

    $from = (string) $request->get_param('From');
    $body = (string) $request->get_param('Body');
    $body_norm = $this->normalize_text($body);

    $key = $this->state_key($from);
    $state = get_transient($key);
    if (!is_array($state)) $state = [];

    // Mostrar menú
    if ($body_norm === 'hola' || $body_norm === 'menu' || $body_norm === 'menú' || empty($state)) {
      $state = ['step' => 'MENU'];
      set_transient($key, $state, self::STATE_TTL);
      return $this->twiml_response($this->txt(self::OPT_WELCOME_MENU));
    }

    $step = $state['step'] ?? 'MENU';

    // Pregunta final SI/NO
    if ($step === 'AFTER_HELP_YN') {
      if ($this->is_yes($body_norm)) {
        $this->clear_state($from);
        return $this->twiml_response($this->txt(self::OPT_AFTER_HELP_YES));
      }
      if ($this->is_no($body_norm)) {
        $state['step'] = 'AFTER_HELP_NEEDS_TEXT';
        set_transient($key, $state, self::STATE_TTL);
        return $this->twiml_response($this->txt(self::OPT_AFTER_HELP_NO));
      }
      return $this->twiml_response("Responde *SI* o *NO*, por favor.");
    }

    // Texto libre (cuando responde NO o opción 8)
    if ($step === 'AFTER_HELP_NEEDS_TEXT') {
      $this->notify_team($from, $body);
      $this->clear_state($from);
      return $this->twiml_response($this->txt(self::OPT_RECEIVED_FREE));
    }

    // Subflujo opción 5
    if ($step === 'OPTION5_HAS_EMAIL_YN') {
      if ($this->is_yes($body_norm)) {
        $state['step'] = 'AFTER_HELP_YN';
        set_transient($key, $state, self::STATE_TTL);
        return $this->twiml_response(
          $this->txt(self::OPT_OPT5_YES) . "\n\n" . $this->txt(self::OPT_AFTER_HELP_ASK)
        );
      }
      if ($this->is_no($body_norm)) {
        $state['step'] = 'AFTER_HELP_YN';
        set_transient($key, $state, self::STATE_TTL);
        return $this->twiml_response(
          $this->txt(self::OPT_OPT5_NO) . "\n\n" . $this->txt(self::OPT_AFTER_HELP_ASK)
        );
      }
      return $this->twiml_response("Responde *SI* o *NO*, por favor.");
    }

    // Interpretar opción 1-8
    $option = $this->extract_option_number($body_norm);
    if ($option === null) {
      return $this->twiml_response("No te he entendido. Responde con un número del *1 al 8* o escribe *menu*.");
    }

    switch ($option) {
      case 1:
        $pdfUrl = trim((string) get_option(self::OPT_PDF_URL, ''));
        $state['step'] = 'AFTER_HELP_YN';
        set_transient($key, $state, self::STATE_TTL);

        $msg = $this->txt(self::OPT_OPT1) . "\n\n" . $this->txt(self::OPT_AFTER_HELP_ASK);
        if ($pdfUrl !== '') return $this->twiml_response($msg, $pdfUrl);
        return $this->twiml_response($msg . "\n(📄 PDF no configurado en el panel del plugin)");

      case 2:
        $state['step'] = 'AFTER_HELP_YN';
        set_transient($key, $state, self::STATE_TTL);
        return $this->twiml_response($this->txt(self::OPT_OPT2) . "\n\n" . $this->txt(self::OPT_AFTER_HELP_ASK));

      case 3:
        $state['step'] = 'AFTER_HELP_YN';
        set_transient($key, $state, self::STATE_TTL);
        return $this->twiml_response($this->txt(self::OPT_OPT3) . "\n\n" . $this->txt(self::OPT_AFTER_HELP_ASK));

      case 4:
        $state['step'] = 'AFTER_HELP_YN';
        set_transient($key, $state, self::STATE_TTL);
        return $this->twiml_response($this->txt(self::OPT_OPT4) . "\n\n" . $this->txt(self::OPT_AFTER_HELP_ASK));

      case 5:
        $state['step'] = 'OPTION5_HAS_EMAIL_YN';
        set_transient($key, $state, self::STATE_TTL);
        return $this->twiml_response($this->txt(self::OPT_OPT5_QUESTION));

      case 6:
        $state['step'] = 'AFTER_HELP_YN';
        set_transient($key, $state, self::STATE_TTL);
        return $this->twiml_response($this->txt(self::OPT_OPT6) . "\n\n" . $this->txt(self::OPT_AFTER_HELP_ASK));

      case 7:
        $state['step'] = 'AFTER_HELP_YN';
        set_transient($key, $state, self::STATE_TTL);
        return $this->twiml_response($this->txt(self::OPT_OPT7) . "\n\n" . $this->txt(self::OPT_AFTER_HELP_ASK));

      case 8:
        $state['step'] = 'AFTER_HELP_NEEDS_TEXT';
        set_transient($key, $state, self::STATE_TTL);
        return $this->twiml_response($this->txt(self::OPT_OPT8_PROMPT));

      default:
        return $this->twiml_response("Opción inválida. Responde con un número del *1 al 8* o escribe *menu*.");
    }
  }

  /** ---------------------------
   *  Helpers TwiML
   *  --------------------------- */
  private function twiml_response($message, $mediaUrl = '') {
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response>\n  <Message>\n    <Body>".$this->xml_escape($message)."</Body>\n";
    if ($mediaUrl !== '') $xml .= "    <Media>".$this->xml_escape($mediaUrl)."</Media>\n";
    $xml .= "  </Message>\n</Response>";
    return new WP_REST_Response($xml, 200, ['Content-Type' => 'text/xml; charset=UTF-8']);
  }

  private function xml_escape($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_XML1, 'UTF-8');
  }

  /** ---------------------------
   *  Text getter
   *  --------------------------- */
  private function txt($opt) {
    $val = get_option($opt, '');
    return is_string($val) ? trim($val) : '';
  }

  /** ---------------------------
   *  Estado / utilidades
   *  --------------------------- */
  private function state_key($from) { return 'twab_state_' . md5((string)$from); }
  private function clear_state($from) { delete_transient($this->state_key($from)); }

  private function normalize_text($text) {
    $t = trim(mb_strtolower((string)$text, 'UTF-8'));
    $t = preg_replace('/\s+/', ' ', $t);
    return $t;
  }

  private function extract_option_number($body_norm) {
    if (preg_match('/\b([1-8])\b/', $body_norm, $m)) return (int)$m[1];
    if (preg_match('/^([1-8])[\.\-\)]/', $body_norm, $m)) return (int)$m[1];
    return null;
  }

  private function is_yes($t) { return in_array($t, ['si','sí','s','ok','vale','de acuerdo','correcto'], true); }
  private function is_no($t)  { return in_array($t, ['no','n','nop','negativo'], true); }

  /** ---------------------------
   *  Email notificación
   *  --------------------------- */
  private function notify_team($from, $text) {
    $to = trim((string) get_option(self::OPT_NOTIFY_EMAIL, get_option('admin_email')));
    if ($to === '') return;

    wp_mail(
      $to,
      'Nueva consulta WhatsApp (Twilio Bot)',
      "Se ha recibido una consulta desde WhatsApp.\n\nFrom: {$from}\nMensaje:\n{$text}\n\n— Enviado por Twilio WhatsApp Bot"
    );
  }

  /** ---------------------------
   *  Firma Twilio (opcional)
   *  --------------------------- */
  private function get_header(WP_REST_Request $request, $name) {
    $headers = $request->get_headers();
    $lname = strtolower($name);
    foreach ($headers as $k => $v) {
      if (strtolower($k) === $lname && is_array($v) && isset($v[0])) return $v[0];
    }
    return '';
  }

  private function validate_twilio_signature(WP_REST_Request $request, $authToken, $signature) {
    if ($signature === '') return false;

    $route = $request->get_route();
    $fullUrl = get_rest_url(null, ltrim($route, '/'));

    $params = $request->get_params();
    unset($params['_wpnonce'], $params['_wp_http_referer']);
    ksort($params);

    $data = $fullUrl;
    foreach ($params as $k => $v) $data .= $k . $v;

    $computed = base64_encode(hash_hmac('sha1', $data, $authToken, true));
    return hash_equals($computed, $signature);
  }

  /** ---------------------------
   *  Admin UI (menú lateral)
   *  --------------------------- */
  public function admin_menu() {
    add_menu_page(
      'WhatsApp Bot',
      'WhatsApp Bot',
      'manage_options',
      'twab-main',
      [$this, 'settings_page'],
      'dashicons-format-chat',
      56
    );

    add_submenu_page('twab-main', 'Configuración', 'Configuración', 'manage_options', 'twab-main', [$this, 'settings_page']);
    add_submenu_page('twab-main', 'Textos', 'Textos', 'manage_options', 'twab-texts', [$this, 'texts_page']);
  }

  public function register_settings() {
    // Config
    register_setting('twab_settings', self::OPT_AUTH_TOKEN,   ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('twab_settings', self::OPT_PDF_URL,      ['sanitize_callback' => 'esc_url_raw']);
    register_setting('twab_settings', self::OPT_NOTIFY_EMAIL, ['sanitize_callback' => 'sanitize_email']);

    // Textos (sanitizamos como textarea)
    $txt_opts = array_keys($this->default_texts());
    foreach ($txt_opts as $opt) {
      register_setting('twab_texts', $opt, ['sanitize_callback' => [$this, 'sanitize_textarea']]);
    }

    // Mensajes adicionales (los que no están en default_texts: ya están incluidos arriba)
    // (Nada extra)
  }

  public function sanitize_textarea($value) {
    // Permitimos saltos de línea, emojis y texto normal
    $value = (string) $value;
    $value = wp_kses($value, []); // sin HTML
    return trim($value);
  }

  public function settings_page() {
    if (!current_user_can('manage_options')) return;
    $endpoint = esc_html(get_rest_url(null, 'twilio-bot/v1/whatsapp'));
    ?>
    <div class="wrap">
      <h1>WhatsApp Bot (Twilio) — Configuración</h1>

      <p><strong>Callback URL (Twilio webhook):</strong><br>
        <code><?php echo $endpoint; ?></code>
      </p>

      <form method="post" action="options.php">
        <?php settings_fields('twab_settings'); ?>

        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="<?php echo esc_attr(self::OPT_AUTH_TOKEN); ?>">Twilio Auth Token (opcional)</label></th>
            <td>
              <input type="password" class="regular-text"
                     id="<?php echo esc_attr(self::OPT_AUTH_TOKEN); ?>"
                     name="<?php echo esc_attr(self::OPT_AUTH_TOKEN); ?>"
                     value="<?php echo esc_attr(get_option(self::OPT_AUTH_TOKEN, '')); ?>">
              <p class="description">Recomendado en producción para validar la firma de Twilio.</p>
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="<?php echo esc_attr(self::OPT_PDF_URL); ?>">URL del PDF (opción 1)</label></th>
            <td>
              <input type="url" class="regular-text"
                     id="<?php echo esc_attr(self::OPT_PDF_URL); ?>"
                     name="<?php echo esc_attr(self::OPT_PDF_URL); ?>"
                     value="<?php echo esc_attr(get_option(self::OPT_PDF_URL, '')); ?>">
              <p class="description">URL pública directa (Twilio la descarga y la envía como adjunto).</p>
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="<?php echo esc_attr(self::OPT_NOTIFY_EMAIL); ?>">Email de notificaciones</label></th>
            <td>
              <input type="email" class="regular-text"
                     id="<?php echo esc_attr(self::OPT_NOTIFY_EMAIL); ?>"
                     name="<?php echo esc_attr(self::OPT_NOTIFY_EMAIL); ?>"
                     value="<?php echo esc_attr(get_option(self::OPT_NOTIFY_EMAIL, get_option('admin_email'))); ?>">
              <p class="description">Recibe las consultas cuando el usuario responde NO o elige la opción 8.</p>
            </td>
          </tr>
        </table>

        <?php submit_button('Guardar configuración'); ?>
      </form>

      <hr>
      <h2>Configurar Twilio (Sandbox)</h2>
      <ol>
        <li>Twilio Console → <strong>Messaging → Try it out → Send a WhatsApp message</strong></li>
        <li>Pestaña <strong>Sandbox settings</strong></li>
        <li>En <strong>“WHEN A MESSAGE COMES IN”</strong> pega el endpoint de arriba</li>
        <li>Método: <strong>POST</strong> y guarda</li>
      </ol>

      <p>Para probar: escribe <strong>hola</strong> o <strong>menu</strong> en WhatsApp.</p>
    </div>
    <?php
  }

  public function texts_page() {
    if (!current_user_can('manage_options')) return;

    $fields = [
      'Menú' => [
        self::OPT_WELCOME_MENU => 'Mensaje de bienvenida + menú (1–8)',
      ],
      'Respuestas 1–4' => [
        self::OPT_OPT1 => 'Opción 1: Info servicios (con PDF si está configurado)',
        self::OPT_OPT2 => 'Opción 2: Primera visita',
        self::OPT_OPT3 => 'Opción 3: Pedir cita (paciente)',
        self::OPT_OPT4 => 'Opción 4: Modificar/Cancelar cita',
      ],
      'Opción 5 (subflujo)' => [
        self::OPT_OPT5_QUESTION => 'Opción 5: Pregunta “¿tienes el mail…?”',
        self::OPT_OPT5_YES      => 'Opción 5: Respuesta si SI',
        self::OPT_OPT5_NO       => 'Opción 5: Respuesta si NO',
      ],
      'Respuestas 6–8' => [
        self::OPT_OPT6        => 'Opción 6: Acceso al centro',
        self::OPT_OPT7        => 'Opción 7: Videollamada/conexión',
        self::OPT_OPT8_PROMPT => 'Opción 8: Prompt para escribir consulta',
      ],
      'Cierre SI/NO' => [
        self::OPT_AFTER_HELP_ASK => 'Pregunta final “¿Hemos resuelto tus dudas?”',
        self::OPT_AFTER_HELP_YES => 'Respuesta si SI',
        self::OPT_AFTER_HELP_NO  => 'Respuesta si NO (pide detalle)',
        self::OPT_RECEIVED_FREE  => 'Confirmación cuando recibimos texto libre',
      ],
    ];

    ?>
    <div class="wrap">
      <h1>WhatsApp Bot (Twilio) — Textos</h1>
      <p>Edita aquí todos los mensajes del bot. No uses HTML; solo texto, emojis y saltos de línea.</p>

      <form method="post" action="options.php">
        <?php settings_fields('twab_texts'); ?>

        <?php foreach ($fields as $sectionTitle => $sectionFields): ?>
          <h2><?php echo esc_html($sectionTitle); ?></h2>
          <table class="form-table" role="presentation">
            <?php foreach ($sectionFields as $opt => $label): ?>
              <tr>
                <th scope="row">
                  <label for="<?php echo esc_attr($opt); ?>"><?php echo esc_html($label); ?></label>
                </th>
                <td>
                  <textarea class="large-text" rows="5"
                            id="<?php echo esc_attr($opt); ?>"
                            name="<?php echo esc_attr($opt); ?>"><?php echo esc_textarea(get_option($opt, '')); ?></textarea>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php endforeach; ?>

        <?php submit_button('Guardar textos'); ?>
      </form>
    </div>
    <?php
  }
}

new TWAB_Plugin_Editable();
