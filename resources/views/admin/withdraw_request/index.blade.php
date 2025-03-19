@extends('layouts.master')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-12">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Withdraw Request Lists</li>
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
                        <a href="{{ route('admin.agent.withdraw') }}" class="btn btn-primary " style="width: 100px;"> <i
                                class="fas fa-arrow-left mr-2"></i>Back</a>
                    </div>
                    <div class="card " style="border-radius: 20px;">
                        <div class="card-header">
                            <h3>Withdraw Request Lists</h3>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('admin.agent.withdraw') }}" method="GET">
                                <div class="row mt-3">
                                    <div class="col-md-3">
                                        <div class="mb-2">
                                            <label for="exampleFormControlSelect1" class="ms-0">Select Status</label>
                                            <select class="form-control border border-1 border-secondary px-2" id="" name="status">
                                                <option value="all"
                                                    {{ request()->get('status') == 'all' ? 'selected' : '' }}>All
                                                </option>
                                                <option value="0"
                                                    {{ request()->get('status') == '0' ? 'selected' : '' }}>Pending
                                                </option>
                                                <option value="1"
                                                    {{ request()->get('status') == '1' ? 'selected' : '' }}>Approved
                                                </option>
                                                <option value="2"
                                                    {{ request()->get('status') == '2' ? 'selected' : '' }}>Rejected
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label class="form-label text-dark fw-bold" for="inputEmail1">From Date</label>
                                            <input type="date" class="form-control border border-1 border-secondary px-2"
                                                name="start_date" value="{{ request()->start_date }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label class="form-label text-dark fw-bold" for="inputEmail1">To Date</label>
                                            <input type="date" class="form-control border border-1 border-secondary px-2"
                                                id="" name="end_date" value="{{ request()->end_date }}">
                                        </div>
                                    </div>
                                    <div class="col-log-3">
                                        <button type="submit" class="btn btn-primary"
                                            style="margin-top: 32px;">Search</button>
                                        <a href="{{ route('admin.agent.withdraw') }}" class="btn btn-warning"
                                            style="margin-top: 32px;">Refresh</a>
                                    </div>
                                </div>
                            </form>

                            <table id="mytable" class="table table-bordered table-hover">
                                <thead>
                                    <th>#</th>
                                    <th>PlayerId</th>
                                    <th>PlayerName</th>
                                    <th>Requested Amount</th>
                                    <th>Payment Method</th>
                                    <th>Bank Account Name</th>
                                    <th>Bank Account Number</th>
                                    <th>Status</th>
                                    <th>Created_at</th>
                                    <th>Action</th>
                                </thead>
                                <tbody>
                                    @foreach ($withdraws as $withdraw)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $withdraw->user->user_name}}</td>
                                            <td>
                                                <span class="d-block">{{ $withdraw->user->name }}</span>
                                            </td>
                                            <td>{{ number_format($withdraw->amount) }}</td>
                                            {{-- <td>{{ $withdraw->bank->paymentType->name }}</td> --}}
                                            <td>{{ $withdraw->paymentType->name ?? 'N/A' }}</td>

                                            <td>{{ $withdraw->account_name }}</td>
                                            <td>{{ $withdraw->account_number }}</td>
                                            <td>
                                                @if ($withdraw->status == 0)
                                                    <span class="badge text-bg-warning text-warning mb-2">Pending</span>
                                                @elseif ($withdraw->status == 1)
                                                    <span class="badge text-bg-success text-success mb-2">Approved</span>
                                                @elseif ($withdraw->status == 2)
                                                    <span class="badge text-bg-danger text-danger mb-2">Rejected</span>
                                                @endif
                                            </td>

                                            <td>{{ $withdraw->created_at->format('d-m-Y H:i:s') }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <form
                                                        action="{{ route('admin.agent.withdrawStatusUpdate', $withdraw->id) }}"
                                                        method="post">
                                                        @csrf
                                                        <input type="hidden" name="amount"
                                                            value="{{ $withdraw->amount }}">
                                                        <input type="hidden" name="status" value="1">
                                                        <input type="hidden" name="player"
                                                            value="{{ $withdraw->user_id }}">
                                                        @if ($withdraw->status == 0)
                                                            <button class="btn btn-success p-1 me-1" type="submit">
                                                                <i class="fas fa-check"></i>
                                                            </button> &nbsp; &nbsp;
                                                        @endif
                                                    </form>
                                                    <form
                                                        action="{{ route('admin.agent.withdrawStatusreject', $withdraw->id) }}"
                                                        method="post">
                                                        @csrf
                                                        <input type="hidden" name="status" value="2">
                                                        @if ($withdraw->status == 0)
                                                            <button class="btn btn-danger p-1 me-1" type="submit">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        @endif
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th>Total Withdraw Amt:</th>

                                        <th>

                                            <span class="text-success">
                                                {{ number_format( $totalWithdrawsAndCounts['totalWithdraws']) }}
                                            </span>
                                        </th>
                                        <th>Total Withdraw Request</th>
                                        <th>{{  $totalWithdrawsAndCounts['totalWithdrawsCount']}} </th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>

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
