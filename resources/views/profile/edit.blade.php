@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')
@section('page-subtitle', 'Manage your account details and password')

@section('content')
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header">Personal information</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label required">Full name</label>
                                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control" placeholder="+256 …">
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label required">Email address</label>
                                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                                <div class="form-text">Changing your email updates your sign-in address. Sign in with the new email next time.</div>
                            </div>
                        </div>

                        <button class="btn btn-primary mt-3">Save changes</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Change password</div>
                <div class="card-body">
                    @if($errors->updatePassword->any())
                        <div class="alert alert-danger py-2" style="font-size:.83rem;">
                            @foreach($errors->updatePassword->all() as $error)<div>{{ $error }}</div>@endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="current_password" class="form-label required">Current password</label>
                                <input id="current_password" type="password" name="current_password" class="form-control" required autocomplete="current-password">
                            </div>
                            <div class="col-md-6">
                                <label for="new_password" class="form-label required">New password</label>
                                <input id="new_password" type="password" name="password" class="form-control" required autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label required">Confirm new password</label>
                                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                            </div>
                        </div>
                        <button class="btn btn-outline-primary mt-3">Update password</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-body text-center py-4">
                    <span class="avatar avatar-lg mx-auto mb-3">{{ strtoupper(Str::substr($user->name, 0, 1)) }}</span>
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <div class="text-muted-2 mb-2" style="font-size:.83rem;">{{ $user->email }}</div>
                    <span class="badge badge-soft-primary">{{ $user->role?->label }}</span>
                    <span class="badge badge-soft-{{ $user->status->badgeClass() }}">{{ $user->status->label() }}</span>

                    <hr class="my-4">
                    <dl class="row mb-0 text-start" style="font-size:.83rem;">
                        <dt class="col-5 text-muted-2 fw-normal">Phone</dt>
                        <dd class="col-7">{{ $user->phone ?: '—' }}</dd>
                        <dt class="col-5 text-muted-2 fw-normal">Last sign-in</dt>
                        <dd class="col-7">{{ $user->last_login_at?->format('d M Y, H:i') ?? '—' }}</dd>
                        <dt class="col-5 text-muted-2 fw-normal">Member since</dt>
                        <dd class="col-7">{{ $user->created_at?->format('d M Y') }}</dd>
                    </dl>
                </div>
            </div>

            @if($user->isStudent() && $user->student)
                <div class="card mt-3">
                    <div class="card-header">Academic record</div>
                    <div class="card-body" style="font-size:.83rem;">
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Registration no.</span><strong>{{ $user->student->registration_number }}</strong></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Course</span><span class="text-end">{{ $user->student->course?->name ?? '—' }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Department</span><span class="text-end">{{ $user->student->department?->name ?? '—' }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Year of study</span><strong>Year {{ $user->student->year_of_study }}</strong></div>
                    </div>
                </div>
            @endif

            @if($user->isSupervisor() && $user->companySupervisor)
                <div class="card mt-3">
                    <div class="card-header">Company assignment</div>
                    <div class="card-body" style="font-size:.83rem;">
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Company</span><span class="text-end">{{ $user->companySupervisor->company?->name ?? '—' }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Position</span><span class="text-end">{{ $user->companySupervisor->position ?: '—' }}</span></div>
                    </div>
                </div>
            @endif

            @if($user->isLecturer() && $user->lecturer)
                <div class="card mt-3">
                    <div class="card-header">Academic appointment</div>
                    <div class="card-body" style="font-size:.83rem;">
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Staff number</span><strong>{{ $user->lecturer->staff_number }}</strong></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Department</span><span class="text-end">{{ $user->lecturer->department?->name ?? '—' }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Designation</span><span class="text-end">{{ $user->lecturer->position ?: '—' }}</span></div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
