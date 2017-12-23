@extends( xclydes_larva_config('view.app') )

@section('content')
{!! xclydes_larva_config('list.wrapper.open') !!}
	@if( xclydes_larva_config('list.header.new', false) )
		<div class="row">
			<a href="{{ URL::route($routePrefix . '.create' ) }}" class="btn btn-primary"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>&nbsp;{{ trans(_XCLYDESLARVA_NS_RESOURCES_ . '::buttons.new') }}</a>
		</div>
	@endif
	<table class="table table-striped">
		<thead>
			<tr>
			@foreach ($form->getDisplayedFields() as $displayKey=>$displayField)
			   	<th>{{ $displayField }} </th>
			@endforeach
			<th width="10%">Actions</th>
			</tr>
		</thead>
		<tbody>
			@if (count($items) > 0)
			   	@foreach ($items as $item)
			   	<tr>
				    @foreach ($form->getDisplayedFields() as $displayKey=>$displayField)
					   	<td>{{  Html::linkRoute($routePrefix . '.edit', LarvaHelper::resolveForDisplay($item, $displayKey, $form), array( $item->getKey() ) ) }} </td>
					@endforeach
					<td width="10%">
						<a href="{{ URL::route($routePrefix . '.edit', array( $item->getKey() ) ) }}" class="btn btn-warning"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></a>
						&nbsp;
						<!-- 
						<a href="{{ URL::route($routePrefix . '.destroy', array( $item->getKey() ) ) }}" class="btn btn-danger"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a>
						 -->
					</td>
				</tr>
				@endforeach
			@else
			    <tr><td colspan="{{ count($form->getDisplayedFields()) + 1 }}">No Records Found</td></tr>
			@endif
		</tbody>
	</table>
	@if( xclydes_larva_config('list.footer.new', false) )
		<div class="row">
			<a href="{{ URL::route($routePrefix . '.create') }}" class="btn btn-primary"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>&nbsp;{{ trans(_XCLYDESLARVA_NS_RESOURCES_ . '::buttons.new') }}</a>
		</div>
	@endif
{!! xclydes_larva_config('list.wrapper.close') !!}
@endsection