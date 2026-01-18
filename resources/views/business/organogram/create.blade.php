<x-app-layout>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --accent: #10b981;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border: #e5e7eb;
            --bg-subtle: #f9fafb;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
        }

        .organogram-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .page-header {
            margin-bottom: 2.5rem;
        }

        .page-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
            margin: 0 0 0.5rem 0;
        }

        .page-header p {
            font-size: 0.9375rem;
            color: var(--text-secondary);
            margin: 0;
            font-weight: 500;
        }

        .form-wrapper {
            background: white;
            border-radius: 0.75rem;
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(0, 0, 0, 0.02);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.75rem;
            align-items: flex-end;
            margin-bottom: 0;
        }

        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: 2fr 1.8fr 1.8fr auto;
                gap: 1.5rem;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.625rem;
            letter-spacing: 0.3px;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.9375rem;
            color: var(--text-primary);
            background-color: white;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .form-control::placeholder {
            color: #d1d5db;
            font-weight: 400;
        }

        .form-control:hover {
            border-color: #d1d5db;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background-color: #fafbff;
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%232563eb' d='M10.293 3.293L6 7.586 1.707 3.293A1 1 0 00.293 4.707l5 5a1 1 0 001.414 0l5-5a1 1 0 10-1.414-1.414z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            padding-right: 2.5rem;
        }

        .form-control:disabled {
            background-color: var(--bg-subtle);
            color: var(--text-secondary);
            cursor: not-allowed;
        }

        .button-group {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: 0.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1b3ba8 100%);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
            transform: translateY(-2px);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }

        .btn-secondary {
            background-color: white;
            color: var(--text-primary);
            border: 1.5px solid var(--border);
        }

        .btn-secondary:hover {
            background-color: var(--bg-subtle);
            border-color: #c4b5fd;
        }

        .input-icon {
            position: relative;
        }

        .input-icon svg {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1rem;
            height: 1rem;
            color: var(--text-secondary);
            pointer-events: none;
        }

        @media (max-width: 640px) {
            .organogram-container {
                padding: 1rem 1rem;
            }

            .form-wrapper {
                padding: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1.25rem;
            }

            .button-group {
                width: 100%;
            }

            .btn {
                width: 100%;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(1rem);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-wrapper {
            animation: fadeInUp 0.4s ease-out;
        }

        .form-group {
            animation: fadeInUp 0.4s ease-out;
        }

        .form-group:nth-child(1) { animation-delay: 0.05s; }
        .form-group:nth-child(2) { animation-delay: 0.1s; }
        .form-group:nth-child(3) { animation-delay: 0.15s; }
        .form-group:nth-child(4) { animation-delay: 0.2s; }
    </style>

    <div class="organogram-container">

        <!-- Page Header -->
        <div class="page-header">
            <h1>Add Organogram Position</h1>
            <p>Define roles and reporting structure</p>
        </div>

        <!-- Form Card -->
        <form method="POST"
              action="{{ route('business.organogram.store', $business->slug) }}"
              class="form-wrapper">

            @csrf

            <!-- Form Grid -->
            <div class="form-grid">

                <!-- Position Title -->
                <div class="form-group">
                    <label for="position-title">Position Title</label>
                    <input
                        id="position-title"
                        type="text"
                        name="title"
                        required
                        placeholder="e.g. HR Manager"
                        class="form-control">
                </div>

                <!-- Reports To -->
                <div class="form-group">
                    <label for="reports-to">Reports To</label>
                    <select
                        id="reports-to"
                        name="parent_id"
                        class="form-control">
                        <option value="">— Top Level —</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}">
                                {{ $parent->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Employee -->
                <div class="form-group">
                    <label for="employee">Employee</label>
                    <select
                        id="employee"
                        name="employee_id"
                        class="form-control">
                        <option value="">— Vacant —</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Button -->
                <div class="form-group">
                    <div class="button-group">
                        <button
                            type="submit"
                            class="btn btn-primary">
                            Save Position
                        </button>
                    </div>
                </div>

            </div>
        </form>

    </div>
</x-app-layout>
