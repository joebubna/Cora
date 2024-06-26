<?PHP
namespace Cora\App;

class Controller extends \Cora\Controller
{   
    protected $db;
    protected $app;
    protected $event;
    protected $session;
    protected $cookie;
    protected $redirect;
    protected $auth;
    
    public function __construct($container = null)
    {
        parent::__construct($container); 
        
        $this->app = $container;
        $this->load = $this->app->load();
        $this->db = $this->app->db();
        $this->event = $this->app->event();
        $this->session = $this->app->session();
        $this->cookie = $this->app->cookie();
        $this->redirect = $this->app->redirect();
        
        $this->auth = $this->app->auth($this->session->user, $this->session->loginSecure, 'email');
        $this->site->user = $this->data->cuser = $this->auth->userGetCurrent();
        $this->data->redirect = $this->redirect;
        
        // TEMPLATE SETUP
        if ($this->site->user->primaryRole->name == 'Admin' || $this->site->user->primaryRole->name == 'Dev') {
            $this->data->navbar = $this->load->view('_partials/template/adminNav', $this->data, true);
        } 
        else if ($this->site->user->primaryRole->name == 'User') {
            $this->data->navbar = $this->load->view('_partials/template/userNav', $this->data, true);
        }
        else {
            $this->data->navbar = $this->load->view('_partials/template/defaultNav', $this->data, true);
        }
    }
    
    
    /**
     *  Takes a database object as input, and sets some query parameters on it based off GET variables.
     *  This is a helper function for setting up searching/filtering/pagination on result sets without 
     *  clutting up each controller with these repetitive settings.
     */
    protected function _setQuery($query, $limit = 16, $validColumns = array('firstName', 'lastName', 'email'))
    {
        $filters = array();
        $pageOffset = $this->input->get('offset', 0);
        $orderBy    = $this->input->get('orderBy', $validColumns[0]);
        $orderDir   = $this->input->get('orderDir', 'asc');
        $orderBy2   = $this->input->get('orderBy2', $validColumns[1]);
        $orderDir2  = $this->input->get('orderDir2', 'asc');
        $letterFilter = $this->input->get('letterFilter', false);
        $searchFilter = $this->input->get('query', false);
        $offset = $pageOffset * $limit;
        
        // Set orderBy. A default should be provided.
        if ($orderBy && in_array($orderBy, $validColumns)) {
            $query->orderBy($orderBy, $orderDir);
            $filters['orderBy'] = $orderBy;
            $filters['orderDir'] = $orderDir;
        }
        
        if ($orderBy2 && in_array($orderBy2, $validColumns)) {
            $query->orderBy($orderBy2, $orderDir2);
            $filters['orderBy2'] = $orderBy2;
            $filters['orderDir2'] = $orderDir2;
        }
        
        // If a letter to filter by is provided (i.e. "A"), then filter on the currently selected OrderBy column.
        if ($letterFilter) {
            // Passing user provided variables for a column name is NOT safe (SQL injection vuln), thus we are
            // checking here that it matches valid options.
            if (in_array($orderBy, $validColumns)) {
                $query->where($orderBy, $letterFilter.'%', 'LIKE');
                $filters['letterFilter'] = $letterFilter;
            }
        }
        
        if ($searchFilter) {
            if (in_array($orderBy, $validColumns)) {
                $search = '%'.$searchFilter.'%';
                $query->where($orderBy, $search, 'LIKE');
                $filters['query'] = $searchFilter;
            }
        }
        
        if ($limit) {
            $query->limit($limit);
        }
        
        if ($offset) {
            $query->offset($offset);
        }
        
        return $filters;
    }

}