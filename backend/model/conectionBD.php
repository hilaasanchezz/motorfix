<?php

/**
 * Conexión con la base de datos MySQL de MotorFix mediante PDO.
 *
 * Usa el patrón Singleton: solo existe una instancia de la clase y, por tanto,
 * una única conexión PDO que comparten todos los modelos.
 *
 * Ejemplo de uso:
 * <code>
 * $pdo = Conection::getInstance()->getPDO();
 * $stmt = $pdo->prepare("SELECT * FROM clientes WHERE dni = :dni");
 * $stmt->execute([':dni' => $dni]);
 * </code>
 *
 * @author Hilario Sánchez Pérez
 */
class Conection {
    /** @var string Servidor de MySQL */
    private string $host    = 'localhost';
    /** @var string Nombre de la base de datos */
    private string $db      = 'motorfix';
    /** @var string Usuario de MySQL */
    private string $user    = 'root';
    /** @var string Contraseña de MySQL */
    private string $pass    = '1234';
    /** @var string Juego de caracteres de la conexión */
    private string $charset = 'utf8mb4';

    /** @var Conection|null Instancia única de la clase (Singleton) */
    private static ?Conection $instance = null;

    /** @var PDO|null Instancia de PDO */
    private ?PDO $pdo = null;

    /**
     * Constructor privado para evitar la instanciación directa con 'new'.
     * Abre la conexión con la base de datos.
     *
     * Si no se puede conectar, devuelve un JSON con el error (código 500)
     * y termina la ejecución.
     */
    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (\PDOException $e) {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['success' => false, 'msg' => 'Error de conexión: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Devuelve la instancia única de la clase, creándola la primera vez.
     *
     * @return Conection La instancia compartida
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Devuelve el objeto PDO para hacer consultas.
     *
     * @return PDO La conexión abierta
     */
    public function getPDO(): PDO {
        return $this->pdo;
    }

    /**
     * Evita que la instancia sea clonada.
     */
    private function __clone() {}

    /**
     * Evita que la instancia sea deserializada.
     *
     * @throws Exception Siempre
     */
    public function __wakeup() {
        throw new \Exception("No se permite deserializar esta clase.");
    }
}
