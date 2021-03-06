<?php namespace SamagTech\Crud\Exceptions;

/**
 * Eccezione per l'upload dei file
 *
 * @author Alessandro Marotta
 */
class UploadException extends AbstractCrudException {

    /**
     * Messaggio di default se non è settato nel costruttore
     *
     * @var string
     */
    protected string $customMessage = 'Errore durante l\'upload della risorsa';

    /**
     * Lista dei file che hanno un errore
     *
     * @var string[]
     * @access private
     */
    private array $errors;

    //-------------------------------------------------------------------------------------------------------------

    /**
     * Costruttore.
     *
     * @param string[]  $errors    Lista dei nomi di file con errori
     * @param string    $message   Messaggio dell'eccezione (Default 'null')
     * @param int       $code      Codice di errore dell'eccezione ( Default 'null')
     * @param Exception $previous  Eccezione precedente (Default 'null')
     */
    public function __construct( array $errors = [], $message = null, $code = null, \Exception $previous = null ) {

        // Controllo se è settato il messaggio
        $message ??= $this->customMessage;

        // Controllo se è settato il codice
        $code   ??= $this->httpCode;

        // Setto l'array degli errori
        $this->errors = $errors;

        parent::__construct($message, $code, $previous);
    }

    //-------------------------------------------------------------------------------------------------------------

    /**
     * Restituisce la lista dei file con errori
     *
     * @return string[]
     */
    public function getErrors() : array {
        return $this->errors;
    }

    //-------------------------------------------------------------------------------------------------------------
}