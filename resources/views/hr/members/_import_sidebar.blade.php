<div class="space-y-4">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="font-extrabold text-gray-950 mb-3">Column Guide</h2>
        <div class="space-y-1 text-xs font-semibold">
            <p class="text-[#1a5632] font-bold uppercase tracking-widest text-[10px] mb-1">Required</p>
            <p><code class="bg-gray-100 px-1 rounded">roll_number</code> — unique roll / S.N.</p>
            <p><code class="bg-gray-100 px-1 rounded">first_name</code></p>
            <p><code class="bg-gray-100 px-1 rounded">last_name</code></p>
        </div>
        <div class="mt-3 space-y-1 text-xs font-semibold">
            <p class="text-gray-400 font-bold uppercase tracking-widest text-[10px] mb-1">Optional</p>
            <p><code class="bg-gray-100 px-1 rounded">middle_name</code></p>
            <p><code class="bg-gray-100 px-1 rounded">dob_bs</code> — e.g. <span class="text-gray-500">2080-06-05</span></p>
            <p><code class="bg-gray-100 px-1 rounded">dob</code> — AD date (YYYY-MM-DD)</p>
            <p><code class="bg-gray-100 px-1 rounded">gender</code> — male / female / other</p>
            <p><code class="bg-gray-100 px-1 rounded">mobile</code> / <code class="bg-gray-100 px-1 rounded">guardian_contact</code></p>
            <p><code class="bg-gray-100 px-1 rounded">father_name</code> / <code class="bg-gray-100 px-1 rounded">mother_name</code></p>
            <p><code class="bg-gray-100 px-1 rounded">guardian_name</code></p>
            <p><code class="bg-gray-100 px-1 rounded">email</code></p>
            <p><code class="bg-gray-100 px-1 rounded">registration_no</code></p>
            <p><code class="bg-gray-100 px-1 rounded">address_en</code></p>
            <p><code class="bg-gray-100 px-1 rounded">stream</code> / <code class="bg-gray-100 px-1 rounded">section</code></p>
            <p><code class="bg-gray-100 px-1 rounded">member_type</code> — overrides default</p>
            <p><code class="bg-gray-100 px-1 rounded">login_user_id</code> — username</p>
            <p><code class="bg-gray-100 px-1 rounded">password</code> — plain text (hashed on save)</p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="font-extrabold text-gray-950 mb-3">IEMIS Auto-Map</h2>
        <p class="text-xs text-gray-500 mb-2">These IEMIS column names are recognized automatically:</p>
        <div class="space-y-1 text-xs font-semibold">
            <p><span class="font-mono text-gray-500">FullName</span> → split into first / middle / last</p>
            <p><span class="font-mono text-gray-500">Student Id</span> → registration_no</p>
            <p><span class="font-mono text-gray-500">DOB</span> → dob_bs (BS date)</p>
            <p><span class="font-mono text-gray-500">S.N.</span> → roll_number</p>
            <p><span class="font-mono text-gray-500">Father Name</span> / <span class="font-mono text-gray-500">Mother Name</span></p>
            <p><span class="font-mono text-gray-500">Contact Number</span> → mobile</p>
            <p><span class="font-mono text-gray-500">Permanent Address</span> → address</p>
        </div>
    </div>

    <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4">
        <p class="text-xs font-semibold text-amber-800">
            <strong>Duplicate check:</strong> If a roll number already exists in the same organization, the row is skipped with an error. Resolve by editing the existing member first.
        </p>
    </div>
</div>
