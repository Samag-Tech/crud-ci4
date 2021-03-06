<?php namespace SamagTech\Crud\Exceptions;

/**
 * Eccezione per validazione in fase di creazione e modifica dei dati.
 *
 * @author Alessandro Marotta
 */
class CreateException extends AbstractCrudException {

    /**
     * Messaggio di default se non è settato nel costruttore
     *
     * @var string
     */
    protected string $customMessage = 'Errore durante la creazione della risorsa';

}