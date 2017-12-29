@extends( xclydes_larva_config('view.app') )

@section('content')
{!! xclydes_larva_config('list.wrapper.open') !!}
	@if( xclydes_larva_config('list.header.new', false) )
		<div class="row">
			<a href="{{ URL::route($routePrefix . '.create' ) }}" class="btn btn-primary"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>&nbsp;{{ trans( xclydes_larva_resouce('buttons.new') ) }}</a>
		</div>
	@endif
	{!! $grid->render() !!}
	@if( xclydes_larva_config('list.footer.new', false) )
		<div class="row">
			<a href="{{ URL::route($routePrefix . '.create') }}" class="btn btn-primary"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>&nbsp;{{ trans( xclydes_larva_resouce( 'buttons.new' ) ) }}</a>
		</div>
	@endif
{!! xclydes_larva_config('list.wrapper.close') !!}
@endsection