@extends('layouts.master')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-12">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active">ShanWinLose</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-end mb-3">

                </div>
                <div class="card " style="border-radius: 20px;">
                    <div class="card-header">
                        <h3>Shan Win / Lose </h3>
                    </div>
                    <form role="form" class="text-start" action="{{ route('admin.report.shan.index') }}" method="GET">
                        <div class="row ml-5">
                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label class="form-label text-dark fw-bold" for="inputEmail1">From Date</label>
                                    <input type="date" class="form-control border border-1 border-secondary px-2"
                                        id="inputEmail1" name="start_date">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="mb-3">
                                    <label class="form-label text-dark fw-bold" for="inputEmail1">To Date</label>
                                    <input type="date" class="form-control border border-1 border-secondary px-2"
                                        id="inputEmail1" name="end_date">
                                </div>
                            </div>
                            <div class="col-log-3">
                                <button type="submit" class="btn btn-primary" style="margin-top: 32px;">Search</button>
                                <a href="{{ route('admin.report.shan.index') }}" class="btn btn-warning" style="margin-top: 32px;">Refresh</a>
                            </div>
                        </div>
                    </form>
                    <div class="card-body">
                        <table id="mytable" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Agent Name</th> <!-- Display the agent's name -->
                                    <th>Player Name</th>
                                    <th>Transaction Count</th>
                                    <th>Total Transaction Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($reportTransactions->isEmpty())
                                <tr>
                                    <td colspan="6" class="text-center">No transaction data available.</td>
                                </tr>
                                @else
                                @foreach ($reportTransactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->user_id }}</td>
                                    <td>{{ $transaction->agent_name }}</td> <!-- Display agent name -->

                                    <td>{{ $transaction->player_name }}</td> <!-- Display player name -->
                                    <td>{{ $transaction->transaction_count }}</td>
                                    <td>{{ number_format($transaction->total_transaction_amount, 2) }}</td>
                                    <td>
                                        <a href="{{ route('admin.report.shan.show', $transaction->user_id) }}"
                                            class="btn btn-info">Detail</a>
                                    </td>
                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
    </div>
</section>
@endsection