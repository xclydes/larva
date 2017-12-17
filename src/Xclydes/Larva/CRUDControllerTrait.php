<?php
namespace  Xclydes\Larva;

use \View;
use \Request;
use \Input;
use Kris\LaravelFormBuilder\FormBuilderTrait;

trait  CRUDControllerTrait {
	
	use FormBuilderTrait;
	
	/**
	 *
	 */
	protected abstract function getModelClass();
	
	/**
	 * @return string
	*/
	protected function getModelClassName() {
		return class_basename( (string) $this->getModelClass() );
	}
	
	/**
	 * @param unknown $id
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
	 * for this controler.
	 */
	protected function getRoutePrefix() {
		return strtolower( $this->getModelClassName() );
	}
	
	/**
	 * Generates the dot notation for the action and 
	 * instance specified as it would to this controller.
	 * @param unknown $instance The instance to which the
	 * path should reference if necessary.
	 * @param unknown $dst The action to be included in the URL.
	 * @return string The route description to be used.
	 */
	protected function getDestination( $instance, $dst ) {
		return $this->getRoutePrefix() . '.' . $dst;
	}
	
	/**
	 * Triggers the processes necessary to generate the form
	 * this controller links to.
	 * @param stdclass $instance The object to used as reference.
	 * @return \Kris\LaravelFormBuilder\Form The form implementation
	 * which is to be rendered.
	 */
	protected function createForm( $instance) {
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
            'route_prefix' => $this->getRoutePrefix()
		]);
		return $frm;
	}
	
	/*-- User Operations --*/
	
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$cls = $this->getModelClass();
		$routePrefix = $this->getRoutePrefix();
		//Get the worker instance
		$worker = $this->getModelInstance( null );
		//Get the form data
		$form = $this->createForm( $worker );
		//Get the display fields
		$displayFields = $form->getDisplayedFields();
		//Get all the entries
		$items = $cls::all();
		// load the view and pass the nerds
		return View::make( _XCLYDESLARVA_NS_RESOURCES_ . "::entity_list" )
		->with(compact('cls', 'routePrefix', 'items', 'worker', 'form', 'displayFields') );
	}
	
	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return $this->doAddEdit( null );
	}
	
	
	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
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
	 */
	protected function doAddEdit( $id ) {
		$instance = $this->getModelInstance( $id );
		// load the view and pass the nerds
		return View::make(_XCLYDESLARVA_NS_RESOURCES_ . "::entity_addedit")
		->with('instance', $instance)
		->with('form', $this->createForm( $instance ) )
		->with('routePrefix', $this->getRoutePrefix());
	}
	
	/*-- Storage Manipulation --*/
	
	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		return $this->doSaveUpdate( null );
	}
	
	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
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
			trans(_XCLYDESLARVA_NS_RESOURCES_ . '::messages.' . $msgType, ['type'=>$this->getModelClassName()]);
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