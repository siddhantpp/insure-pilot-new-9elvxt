@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <!-- Hero Section -->
    <div class="jumbotron bg-light p-5 rounded-3 mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4">Welcome to Insure Pilot</h1>
                <p class="lead">Streamline your insurance document management with our new Documents View feature</p>
                <p class="mb-4">Efficiently review, process, and manage insurance documents in a focused environment designed for productivity.</p>
                
                @if(auth()->check())
                    <a href="{{ route('documents.index') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-file-earmark-text me-2"></i>View Documents
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In to Get Started
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-lg ms-2">
                        <i class="bi bi-person-plus me-2"></i>Register
                    </a>
                @endif
            </div>
            <div class="col-lg-4 d-none d-lg-block text-center">
                <img src="{{ asset('images/document-illustration.svg') }}" alt="Document management illustration" class="img-fluid" width="300">
            </div>
        </div>
    </div>

    <!-- Feature Highlights -->
    <h2 class="mb-4 text-center">Key Features</h2>
    <div class="row mb-5">
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="feature-icon bg-primary bg-opacity-10 p-3 rounded-circle mb-3 mx-auto" style="width: 80px; height: 80px;">
                        <i class="bi bi-fullscreen text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h3 class="card-title h5">Full-Screen Document Viewer</h3>
                    <p class="card-text">Review documents in a distraction-free environment with Adobe Acrobat PDF integration</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="feature-icon bg-primary bg-opacity-10 p-3 rounded-circle mb-3 mx-auto" style="width: 80px; height: 80px;">
                        <i class="bi bi-tags text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h3 class="card-title h5">Smart Metadata Management</h3>
                    <p class="card-text">Edit metadata fields with type-ahead filtering and intelligent field dependencies</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="feature-icon bg-primary bg-opacity-10 p-3 rounded-circle mb-3 mx-auto" style="width: 80px; height: 80px;">
                        <i class="bi bi-check2-circle text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h3 class="card-title h5">Efficient Processing</h3>
                    <p class="card-text">Mark documents as processed and track your work with comprehensive audit history</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Getting Started -->
    <div class="bg-light p-4 rounded-3 mb-5">
        <h2 class="mb-4">Getting Started</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">1</div>
                    </div>
                    <div>
                        <h3 class="h5">Access Your Documents</h3>
                        <p>Navigate to the Documents section to view your list of documents ready for processing.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">2</div>
                    </div>
                    <div>
                        <h3 class="h5">Review and Edit</h3>
                        <p>Click on any document to open the full-screen viewer and edit metadata as needed.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">3</div>
                    </div>
                    <div>
                        <h3 class="h5">Process Documents</h3>
                        <p>Mark documents as processed when your review is complete to track your progress.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">4</div>
                    </div>
                    <div>
                        <h3 class="h5">Track History</h3>
                        <p>View document history to see all changes and actions performed.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            @if(auth()->check())
                <a href="{{ route('documents.index') }}" class="btn btn-primary">
                    <i class="bi bi-file-earmark-text me-2"></i>Go to Documents
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In to Start
                </a>
            @endif
        </div>
    </div>

    <!-- Support and Resources -->
    <div class="row mb-5">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title"><i class="bi bi-question-circle me-2 text-primary"></i>Need Help?</h3>
                    <p class="card-text">Our support team is ready to assist you with any questions about using the Documents View feature.</p>
                    <a href="{{ route('support') }}" class="btn btn-outline-primary">Contact Support</a>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title"><i class="bi bi-book me-2 text-primary"></i>Documentation</h3>
                    <p class="card-text">Access comprehensive guides and tutorials for getting the most out of Insure Pilot.</p>
                    <a href="{{ route('documentation') }}" class="btn btn-outline-primary">View Documentation</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Demo Section -->
<div class="bg-light py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2>See Documents View in Action</h2>
                <p class="lead">Watch a quick demo of how the Documents View feature can streamline your workflow.</p>
                <p>Learn how to efficiently manage policy documents, loss reports, and claims in a single interface.</p>
                <button class="btn btn-primary mt-2" 
                       data-bs-toggle="modal" 
                       data-bs-target="#demoVideoModal"
                       aria-label="Watch demo video">
                    <i class="bi bi-play-circle me-2"></i>Watch Demo
                </button>
            </div>
            <div class="col-lg-6">
                <div class="position-relative rounded shadow-sm overflow-hidden">
                    <img src="{{ asset('images/documents-view-preview.jpg') }}" alt="Documents View interface preview" class="img-fluid">
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <button class="btn btn-primary btn-lg rounded-circle p-3" 
                               data-bs-toggle="modal" 
                               data-bs-target="#demoVideoModal"
                               aria-label="Play demo video">
                            <i class="bi bi-play-fill" style="font-size: 2rem;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Demo Video Modal -->
<div class="modal fade" id="demoVideoModal" tabindex="-1" aria-labelledby="demoVideoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="demoVideoModalLabel">Documents View Demo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9">
                    <iframe src="about:blank" data-src="{{ asset('videos/documents-view-demo.mp4') }}" title="Documents View demo video" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load demo video only when modal is opened -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const demoVideoModal = document.getElementById('demoVideoModal');
        if (demoVideoModal) {
            demoVideoModal.addEventListener('shown.bs.modal', function () {
                const iframe = this.querySelector('iframe');
                const videoSrc = iframe.getAttribute('data-src');
                iframe.setAttribute('src', videoSrc);
            });
            
            demoVideoModal.addEventListener('hidden.bs.modal', function () {
                const iframe = this.querySelector('iframe');
                iframe.setAttribute('src', 'about:blank');
            });
        }
    });
</script>
@endsection