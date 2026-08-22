<x-app-layout title="{{ $page }}">

    @include('locations._access_nav')

    <div class="row g-4">
        <!-- Form Card -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" id="card-header">Add New Location</h5>
                </div>
                <div class="card-body p-4" id="locationsFormContainer">
                    @include('locations._form')
                </div>
            </div>
        </div>

        <!-- Locations Table Card -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Locations</h5>
                    <div class="d-flex gap-2">
                        <div id="exportButtons"></div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive" id="locationsContainer">
                        <div class="text-center py-4">{{ loader() }}</div>
                    </div>
                </div>
            </div>
        </div>



        @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js/minified/introjs.min.css">
        @endpush

        @push('scripts')
        <script src="{{ asset('js/main/locations.js') }}" type="module"></script>
        <script src="https://cdn.jsdelivr.net/npm/intro.js/minified/intro.min.js"></script>
        <script>
            $(document).ready(() => {
                getLocations()
            });
            document.addEventListener("DOMContentLoaded", function() {
                $("#startTour").on("click", function() {
                    const intro = introJs();
                    intro.setOptions({
                        steps: [{
                                element: "#locationsFormContainer",
                                title: "Add New Location",
                                intro: "Use this form to add a new company location."
                            },
                            {
                                element: "#locationsContainer",
                                title: "Locations Table",
                                intro: "Here, you can view, edit, and manage all locations."
                            },
                            {
                                element: "#submitButton",
                                title: "Save Location",
                                intro: "Click this button to save a new location."
                            },
                            {
                                element: "#helpButton", // Ensure the Help button has this ID
                                title: "Need Help?",
                                intro: "Click this button for more details or support on managing locations.",
                                position: "left"
                            },
                            {
                                title: "Tour Completed 🎉",
                                intro: "That's it! You're now ready to manage locations efficiently."
                            }
                        ],
                        showProgress: true,
                        showBullets: false,
                        exitOnOverlayClick: false,
                        exitOnEsc: true,
                        nextLabel: "Next",
                        prevLabel: "Back",
                        doneLabel: "Finish"
                    });

                    intro.start();
                });
            });
        </script>
        @endpush

</x-app-layout>