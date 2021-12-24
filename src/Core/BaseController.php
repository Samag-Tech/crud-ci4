<?php namespace SamagTech\Core;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\Response;
use SamagTech\Contracts\Service;
use CodeIgniter\API\ResponseTrait;
use SamagTech\Crud\Traits\CrudTrait;
use SamagTech\ExcelLib\ExcelException;
use SamagTech\Crud\Singleton\CurrentUser;
use SamagTech\Exceptions\CreateException;
use SamagTech\Exceptions\DeleteException;
use SamagTech\Exceptions\UpdateException;
use SamagTech\Contracts\ControllerFactory;
use SamagTech\Exceptions\GenericException;
use SamagTech\Exceptions\ValidationException;
use SamagTech\Exceptions\ResourceNotFoundException;

/**
 * Classe astratta per la definizione di un nuovo CRUD.
 *
 * @implements CrudInterface
 * @extends Controller
 * @abstract
 */
abstract class BaseController extends Controller implements ControllerFactory {

    use ResponseTrait, CrudTrait;

    /**
     * Istanza del servizio
     *
     * @var SamagTech\Contracts\Service
     * @access public
     */
    public Service $service;

    /**
     * Variabile che contiene i dati inerenti all'utente
     * autenticato tramite JWT
     *
     * @var object|null
     * @access public
     */
    public ?object $currentUser = null;

    /**
     * Array contenente i messaggi di default
     * per le risposte delle API
     *
     * @var array
     * @access public
     */
    public array $messages = [
        'create'        =>  'La risorsa è stata creata',
        'retrieve'      =>  'Lista risorse',
        'update'        =>  'La risorsa è stata modificata',
        'delete'        =>  'La risorsa è stata cancellata',
        'export'        =>  'Il file excel è pronto',
    ];


    /**
     * Service di default
     *
     * @var string
     * @access protected
     */
    protected ?string $defaultService = null;

    /**
     * Lista di servizi esterni al default service
     *
     * Es. [
     *  'token1' => '\App\Modules\Examples\Services\Examples1::class ',
     *  'token2' => '\App\Modules\Examples\Services\Examples2::class ',
     * ]
     *
     * @var string[]
     * @access protected
     */
    protected ?array $services = null;

    /**
	* An array of helpers to be loaded automatically upon
	* class instantiation. These helpers will be available
	* to all other controllers that extend BaseController.
	*
	* @var array
	*/
	protected $helpers = [];

    /**
     * Nome del modulo da attivare
     *
     * @var string|null
     * @access private
     */
    private ?string $moduleName = null;

	/**
	 * Constructor.
	 */
	public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {

        // Do Not Edit This Line
		parent::initController($request, $response, $logger);

		//--------------------------------------------------------------------
		// Preload any models, libraries, etc, here.
		//--------------------------------------------------------------------
		// E.g.:
        // $this->session = \Config\Services::session();
    }

    //--------------------------------------------------------------------------------------------

    /**
     * Costruttore.
     *
     */
    public function __construct() {

        // Recupero i dati dell'utente autenticato
        $this->currentUser = CurrentUser::getIstance()->getProperty();

        // Servizio di default
        if ( is_null($this->defaultService) ) {
            die('Il servizio di default non è impostato');
        }

        // Inizializzo il servizio da utilizzare
        $this->service = $this->makeService($this->currentUser->app_token ?? null);

        // Recupero il nome del modulo
        $this->moduleName = $this->getClassName();

    }

    //--------------------------------------------------------------------------------------------

    /**
     * {@inheritDoc}
     *
     * @implements Factory
     *
     */
    public function makeService(?string $token = null): Service {

        if ( ! is_null($token) && ! is_null($this->services) && isset($this->services[$token]) ) {
            return new $this->services[$token]($this->logger);
        }

        $defaultService = $this->defaultService;
        return new $defaultService($this->logger);
    }

    //--------------------------------------------------------------------------------------------

    /**
     * {@inheritDoc}
     */
    public function create() : Response {

        $resource = null;

        // Recupero i dati della risorsa creata
        try {
            $resource = $this->service->create($this->request);
        }
        catch(ValidationException $e) {
            return $this->failValidationErrors($e->getValidationErrors(), $e->getHttpCode());
        }
        catch(CreateException | GenericException $e) {
            return $this->fail($e->getMessage(), $e->getHttpCode());
        }

        return $this->respondCreated($resource);

    }

    //--------------------------------------------------------------------------------------------

    /**
     * {@inheritDoc}
     *
     */
    public function retrieve() : Response {

        try {
            $data = $this->service->retrieve($this->request);
        }
        catch(ResourceNotFoundException $e ) {
            return $this->failNotFound(message:$e->getMessage(), code: $e->getHttpCode());
        }
        catch(GenericException $e) {
            return $this->fail($e->getMessage(), $e->getHttpCode());
        }

        $data['message'] = $this->getResponseMessage('retrieve');

        return $this->respond($data, 200);
    }

    //--------------------------------------------------------------------------------------------

    /**
     * {@inheritDoc}
     *
     */
    public function update(int|string $id ) : Response {

        // Recupero l'identificativo della risorsa appena creata
        try {
            $this->service->update($this->request,$id);
        }
        catch(ValidationException $e) {
            return $this->failValidationErrors($e->getValidationErrors(), $e->getHttpCode());
        }
        catch ( ResourceNotFoundException $e ) {
            return $this->failNotFound(message: $e->getMessage(), code: $e->getHttpCode());
        }
        catch(UpdateException | GenericException $e) {
            return $this->fail($e->getMessage(), $e->getHttpCode());
        }

        return $this->respondUpdated(['item_id' => $id, 'message' => $this->getResponseMessage('update')]);
    }

    //--------------------------------------------------------------------------------------------

    /**
     * {@inheritDoc}
     *
     */
    public function delete(int|string $id) : Response {

        // Recupero l'identificativo della risorsa appena creata
        try {
            $this->service->delete($this->request,$id);
        }
        catch ( ResourceNotFoundException $e ) {
            return $this->failNotFound(message: $e->getMessage(), code: $e->getHttpCode());
        }
        catch(DeleteException | GenericException $e) {
            return $this->fail($e->getMessage(), $e->getHttpCode());
        }

        return $this->respondDeleted(['item_id'  =>  $id, 'message' => $this->getResponseMessage('delete')]);
    }

    //-----------------------------------------------------------------------------

    /**
     * Route per l'esportazione Excel della lista
     *
     * @return \CodeIgniter\HTTP\Response
     */
    public function export() : Response {

        try {
            $path = $this->service->export($this->request);
        }
        catch(GenericException $e) {
            return $this->fail($e->getMessage(), $e->getHttpCode());
        }
        catch (ExcelException $e ) {
            return $this->fail($e->getMessage());
        }

        return $this->respond(['export_path' => $path, 'message' => $this->getResponseMessage('export')], 200);
    }

    //---------------------------------------------------------------------------------------------------
}