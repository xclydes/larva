<?php
namespace  Xclydes\Larva;

use \View;
use \Request;
use \Input;
use Kris\LaravelFormBuilder\FormBuilderTrait;
use Xclydes\Larva\Contracts\IFormEloquent;
use Xclydes\Larva\Helpers\LarvaHelper;

trait  CRUDControllerTrait {
	
	use FormBuilderTrait;
	
	/**
	 *
	 */
	protected abstract function getModelClass();

	protected function getIndexView() {
        return View::make( _XCLYDESLARVA_NS_RESOURCES_ . "::entity_list" );
    }

    protected function getAddEditView() {
        return View::make(_XCLYDESLARVA_NS_RESOURCES_ . "::entity_addedit");
    }

	/**
	 * @return string
	*/
	protected function getModelClassName() {
		return class_basename( (string) $this->getModelClass() );
	}

	protected function getItemsForPage( $page = null ) {
	    $cls = $this->getModelClass();
	    return $cls::all();
    }

    /**
     * @param string|integer $id
     * @return IFormEloquent|object
     */
	protected function getModelInstance( $id ) {
		$instance = null;
		$cls = $this->getModelClass();
		//If no ID is set
		if( $id == null ) {
			$instance = new $cls;
		} else {
			$instance = $cls::findOrFail( $id );
		}
		return $instance;
	}
	
	/**
	 * Gets the prefix for used to when defining routes
	 * for this controller.
	 */
	protected function getRoutePrefix() {
		return strtolower( $this->getModelClassName() );
	}
	
	/**
	 * Generates the dot notation for the action and 
	 * instance specified as it would to this controller.
	 * @param IFormEloquent|object $instance The instance to which the
	 * path should reference if necessary.
	 * @param string $dst The action to be included in the URL.
	 * @return string The route description to be used.
	 */
	protected function getDestination( $instance, $dst ) {
		return $this->getRoutePrefix() . '.' . $dst;
	}
	
	/**
	 * Triggers the processes necessary to generate the form
	 * this controller links to.
	 * @param IFormEloquent|object $instance The object to used as reference.
	 * @return \Kris\LaravelFormBuilder\Form The form implementation
	 * which is to be rendered.
	 */
	protected function createForm( $instance ) {
		//Assume store
		$dst = 'store';
		//Assume POST
		$method = 'POST';
		//Assume no special route parameters
		$routeParams = array();
		//If the entry exists
		if( $instance->exists ) {
			//Use put
			$method = 'PATCH';
			//Do an update
			$dst = 'update';
			//With the instance ID
			array_push($routeParams, $instance->getKey());
		}
		//Build the route
		$route = route($this->getDestination( $instance, $dst ) , $routeParams);
		//Generate the form
		$frm = $this->form(EloquentForm::class, [
			'method' => $method,//Prefer POST
			'model' => $instance,//Reference this instance
			'url' => $route,//Build the route
            'route_prefix' => $this->getRoutePrefix(),
            'template' => xclydes_larva_config( 'view.form', xclydes_larva_resouce( 'form' )  )
		]);
		return $frm;
	}
	
	/*-- User Operations --*/
	
	/**
	 * Display a listing of the resource.
	 * @return Response
	 */
	public function index()
	{
		$cls = $this->getModelClass();
		$routePrefix = $this->getRoutePrefix();
		//Get the worker instance
		$instance = $this->getModelInstance( null );
		//Get the form data
		$form = $this->createForm( $worker );
		//Get all the entries
		$items = $this->getItemsForPage();
		// load the view and pass the nerds
		return $this->getIndexView()
		    ->with(compact('cls', 'routePrefix', 'items', 'instance', 'form') );
	}
	
	/**
	 * Show the form for creating a new resource.
	 * @return View
	 */
	public function create()
	{
		return $this->doAddEdit( null );
	}
	
	
	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return View
	 */
	public function edit( $id )
	{
		return $this->doAddEdit( $id );
	}
	
	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show( $id )
	{
		return $this->getModelInstance( $id );
	}

    /**
     * @param string $id
     * @return View;
     */
	protected function doAddEdit( $id ) {
		$instance = $this->getModelInstance( $id );
        //Get all the entries
        $items = $this->getItemsForPage();
        // load the view and pass the nerds
		return $this->getAddEditView()
            ->with('instance', $instance)
            ->with('form', $this->createForm( $instance ) )
            ->with('routePrefix', $this->getRoutePrefix())
            ->with('items', $items);
	}
	
	/*-- Storage Manipulation --*/

    /**
     * Store a newly created resource in storage.
     * @return \Illuminate\Http\RedirectResponse
     */
	public function store()
	{
		return $this->doSaveUpdate( null );
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
	 */
	public function update( $id )
	{
		return $this->doSaveUpdate( $id );
	}
	
	/**
	 * @param string $id
	 * @return \Illuminate\Http\RedirectResponse
	 */
	protected function doSaveUpdate( $id ) {
		//Get the instance to be updated
		$instance = $this->getModelInstance( $id );
		//Get the form being processed
		$form = $this->createForm( $instance );
		// It will automatically use current request, get the rules, and do the validation
		if ( !$form->isValid() ) {
			//Return to the edit page wih the errors and inputs
			$redir = redirect()->back()->withErrors( $form->getErrors() )->withInput();
		} else {
			$msgType = $instance->exists ? 'updated' : 'created';
			//Update the instance
			$instance->fill( Input::all() )->save();
			//TODO Set a success flash message
			$statusMsg = trans(_XCLYDESLARVA_NS_RESOURCES_ . '::messages.' . $msgType, ['type'=>$this->getModelClassName()]);
			//Redirect to the listing
			$redir = redirect()->route( $this->getRoutePrefix() . '.index' );
		}
		return $redir;
	}
	
	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy( $id )
	{
		//Get the instance to be updated
		$instance = $this->getModelInstance( $id );
		//Delete it
		$instance->delete();
		//Redirect
		return redirect()->route( $this->getRoutePrefix() . '.index' );
	}
}