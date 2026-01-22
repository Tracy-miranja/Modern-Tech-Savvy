<x-app-layout>
    <div class="flex justify-between mb-4">
        <h2 class="text-xl font-semibold">Organization Structure</h2>

        <a href="{{ route('business.organogram.create', $business->slug) }}"
           class="btn-primary">
            + Add Position
        </a>
    </div>

    <!-- Edit Panel -->
    <div id="edit-panel"
         class="fixed right-0 top-0 h-full w-96 bg-white shadow-lg hidden p-6 z-50 overflow-y-auto">

        <h3 class="text-lg font-semibold mb-4">Edit Position</h3>

        <input type="hidden" id="position_id">

        <label class="block mb-1 text-sm">Title</label>
        <input id="position_title"
               class="w-full border p-2 rounded mb-4">

        {{-- <label class="block mb-1 text-sm">Person Name</label>
        <input id="position_name"
               class="w-full border p-2 rounded mb-4"> --}}
               <label class="block mb-1 text-sm">Employee</label>
<select id="employee_id" class="w-full border p-2 rounded mb-4">
    <option value="">Vacant</option>
</select>


        <div class="flex gap-2">
            <button onclick="savePosition()"
                    class="bg-blue-600 text-white px-4 py-2 rounded">
                Save
            </button>

            <button onclick="addChild()"
                    class="bg-gray-200 px-4 py-2 rounded">
                + Add Child
            </button>

            <button onclick="closePanel()"
                    class="ml-auto text-red-500">
                Close
            </button>
        </div>
    </div>

    <!-- OrgChart Container -->
    <div id="organogram-chart" class="rounded bg-gray-50 shadow"></div>

    <!-- OrgChart JS -->
    <script src="https://balkangraph.com/js/orgchart.js"></script>
    <script>
        // Initialize OrgChart
       const chart = new OrgChart(document.getElementById("organogram-chart"), {
    template: "olivia",
    nodeBinding: {
        field_0: "title",
        field_1: "name",
        img_0: "photo",
    },
    tags: {
        noPhoto: {
            template: "ula"
        }
    }
});

        fetch("{{ route('business.organogram.tree', $business->slug) }}")
    .then(res => res.json())
    .then(data => {

        // 👇 Handle photo / initials fallback
        data.forEach(n => {
            if (!n.photo) {
                n.tags = ['noPhoto'];
                n.img_0 = null;
                n.field_1 = `${n.name} (${n.initials})`;
            }
        });

        chart.load(data);
    });


        // Open edit panel
        function openEditPanel(id) {
            fetch(`/organogram/position/${id}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('position_id').value = data.id;
                    document.getElementById('position_title').value = data.title || '';
                    document.getElementById('position_name').value = data.name || '';
                    document.getElementById('edit-panel').classList.remove('hidden');
                });
        }

        // Close panel
        function closePanel() {
            document.getElementById('edit-panel').classList.add('hidden');
        }

        // Save position
        function savePosition() {
            const positionId = document.getElementById('position_id').value;
            const title = document.getElementById('position_title').value;
            const name = document.getElementById('position_name').value;

            fetch(`/organogram/position/${positionId}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ title, name })
            })
            .then(() => {
                closePanel();
                chart.updateNode({ id: positionId, title, name });
            });
        }

        // Add child position
        function addChild() {
            const parentId = document.getElementById('position_id').value;

            fetch(`/organogram/position`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    title: 'New Position',
                    parent_id: parentId
                })
            })
            .then(res => res.json())
            .then(data => {
                closePanel();
                chart.addNode({ id: data.id, pid: parentId, title: data.title, name: data.name || '' });
            });
        }

        // Chart node click opens edit panel
        chart.on('click', function(sender, args) {
            openEditPanel(args.node.id);
        });
    </script>

    <!-- Styles -->
    <style>
        #organogram-chart {
            height: 800px;
            background: #f9fafb;
        }

        #edit-panel input {
            transition: border 0.2s;
        }

        #edit-panel input:focus {
            border-color: #3b82f6;
            outline: none;
        }
          .btn-primary {
display: flex;
    justify-content: space-between;
    align-items: center;
            background: #cf8f05;
            color: white;
            margin-top: 10px;
            padding: 12px 2px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            width: 10%;
        }
        .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

    </style>
</x-app-layout>
