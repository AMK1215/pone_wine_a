@extends('layouts.master')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-12">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Detail Owner Hierarchy Information</li>
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
                        <a href="{{ route('admin.GetAllOwners') }}" class="btn btn-success " style="width: 100px;"><i
                                class="fas fa-arrow-left text-white  mr-2"></i>Back</a>
                    </div>
                    <div class="card " style="border-radius: 20px;">
                        <div class="card-header">
                            <h3>Owner's Detail Hierarchy Information</h3>
                        </div>

                        <div class="card-body">

                            <table id="seniorTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>TableID</th>
                                        <th>Agent Name</th>
                                        <th>AgentID</th>
                                        <th>AgentPhone</th>
                                        <th>Wallet Balance</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($owner->agents as $index => $agent)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $agent->id }}</td>
                                            {{-- <td>{{ $agent->name }}</td> --}}

                                            <td>
                                                <a
                                                    href="{{ route('admin.AgentPlayerDetail', $agent->id) }}">{{ $agent->name }}</a>
                                            </td>

                                            <!-- Link to player's page -->
                                            <td>
                                                <a
                                                    href="{{ route('admin.AgentPlayerDetail', $agent->id) }}">{{ $agent->user_name }}</a>
                                            </td>
                                            <td>{{ $agent->phone }}</td>
                                            <td>{{ number_format($agent->wallet->balance ?? 0, 2) }}</td>
                                            <td>
                                                <a href="{{ route('admin.AgentPlayerDetail', $agent->id) }}"
                                                    class="btn btn-primary">View Players</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                            </table>
                            <!-- Pagination Links -->

                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    <!-- jQuery -->
    {{-- <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> --}}
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#seniorTable').DataTable();
        });
    </script>
@endsection
