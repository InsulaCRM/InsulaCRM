@extends('layouts.app')

@section('title', 'Hello World Plugin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-globe mr-2"></i>
                        Hello World Plugin
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong>Hello World Plugin is active!</strong>
                    </div>
                    <p>This is a sample plugin demonstrating the CRM plugin architecture.</p>
                    <ul>
                        <li>Registered a sidebar menu item</li>
                        <li>Listening for <code>lead.created</code> events</li>
                        <li>Rendering this custom view</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
