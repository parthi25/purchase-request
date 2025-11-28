<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

// Only super_admin and master can access
if (!in_array($_SESSION['role'], ['super_admin', 'master'])) {
    header("Location: ../index.php");
    exit;
}

include '../common/layout.php'; ?>
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">Role Menu Settings</h1>
    </div>

    <!-- Role Filter -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold capitalize">Filter by Role</span>
                </label>
                <select id="roleFilter" class="select select-bordered w-full max-w-xs">
                    <option value="">All Roles</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title mb-4 capitalize">
                <i class="fas fa-bars"></i>
                <span id="formTitle">Add New Menu Item</span>
            </h2>
            <form id="menuForm" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="id" id="menuId">
                
                <div class="form-control flex-1 min-w-[120px]">
                    <label class="label">
                        <span class="label-text">Role <span class="text-error">*</span></span>
                    </label>
                    <select name="role" id="menuRole" class="select select-bordered w-full" required>
                        <option value="">Select Role</option>
                    </select>
                </div>
                
                <div class="form-control flex-1 min-w-[150px]">
                    <label class="label">
                        <span class="label-text">Menu Label <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="menu_item_label" id="menuLabel" class="input input-bordered w-full" placeholder="e.g., Dashboard" required>
                </div>
                
                <div class="form-control flex-1 min-w-[150px]">
                    <label class="label">
                        <span class="label-text">Menu URL <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="menu_item_url" id="menuUrl" class="input input-bordered w-full" placeholder="e.g., dashboard.php" required>
                </div>
                
                <div class="form-control flex-1 min-w-[180px]">
                    <label class="label">
                        <span class="label-text">Menu Icon</span>
                    </label>
                    <select name="menu_item_icon" id="menuIcon" class="select select-bordered w-full">
                        <option value="">No Icon</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 576 512" fill="currentColor"><path d="M575.8 255.5c0 18-15 32.1-32 32.1h-32l.7 160.2c0 2.7-.2 5.4-.5 8.1V472c0 22.1-17.9 40-40 40H456c-1.1 0-2.2 0-3.3-.1c-1.4 .1-2.8 .1-4.2 .1H416 392c-22.1 0-40-17.9-40-40V448 384c0-17.7-14.3-32-32-32H256c-17.7 0-32 14.3-32 32v64 24c0 22.1-17.9 40-40 40H160 128.1c-1.5 0-3-.1-4.5-.2c-1.2 .1-2.4 .2-3.6 .2H104c-22.1 0-40-17.9-40-40V360c0-.9 0-1.9 .1-2.8V287.6H32c-17 0-32-14-32-32.1c0-9 3-17 10-24L266.4 8c7-7 15-8 22-8s15 2 21 7L564.8 231.5c8 7 12 15 11 24z"/></svg>'>🏠 Dashboard / Home</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 448 512" fill="currentColor"><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"/></svg>'>👤 User</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 640 512" fill="currentColor"><path d="M144 0a80 80 0 1 1 0 160A80 80 0 1 1 144 0zM512 0a80 80 0 1 1 0 160A80 80 0 1 1 512 0zM0 298.7C0 239.8 47.8 192 106.7 192h42.7c15.9 0 31 3.5 44.6 9.7c-1.3 7.2-1.9 14.7-1.9 22.4c0 38.2 16.8 72.5 43.3 96c-.2 0-.4 0-.7 0H21.3C9.6 320 0 310.4 0 298.7zM405.3 320c-.2 0-.4 0-.7 0c26.6-23.5 43.3-57.8 43.3-96c0-7.7-.7-15.2-1.9-22.4c13.6-6.2 28.7-9.7 44.6-9.7h42.7C592.2 192 640 239.8 640 298.7c0 11.8-9.6 21.3-21.3 21.3H405.3zM224 224a96 96 0 1 1 192 0 96 96 0 1 1 -192 0zM128 485.3C128 411.7 187.7 352 261.3 352H378.7C452.3 352 512 411.7 512 485.3c0 14.7-11.9 26.7-26.7 26.7H154.7c-14.7 0-26.7-11.9-26.7-26.7z"/></svg>'>👥 Users</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 512 512" fill="currentColor"><path d="M495.9 166.6c3.2 8.7 .5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6c-4.4 11.9-9.7 23.3-15.8 34.3l-4.7 8.1c-6.6 11-14 21.4-22.1 31.2c-5.9 7.2-15.7 9.6-24.5 6.8l-55.7-17.7c-13.4 10.3-28.2 18.9-44 25.4l-12.5 57.1c-2 9.1-9 16.3-18.2 17.8c-13.8 2.3-28 3.5-42.5 3.5s-28.7-1.2-42.5-3.5c-9.2-1.5-16.2-8.7-18.2-17.8l-12.5-57.1c-15.8-6.5-30.6-15.1-44-25.4L83.1 425.9c-8.8 2.8-18.6 .3-24.5-6.8c-8.1-9.8-15.5-20.2-22.1-31.2l-4.7-8.1c-6.1-11-11.4-22.4-15.8-34.3c-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6c4.4-11.9 9.7-23.3 15.8-34.3l4.7-8.1c6.6-11 14-21.4 22.1-31.2c5.9-7.2 15.7-9.6 24.5-6.8l55.7 17.7c13.4-10.3 28.2-18.9 44-25.4l12.5-57.1c2-9.1 9-16.3 18.2-17.8C227.3 1.2 241.5 0 256 0s28.7 1.2 42.5 3.5c9.2 1.5 16.2 8.7 18.2 17.8l12.5 57.1c15.8 6.5 30.6 15.1 44 25.4l55.7-17.7c8.8-2.8 18.6-.3 24.5 6.8c8.1 9.8 15.5 20.2 22.1 31.2l4.7 8.1c6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z"/></svg>'>⚙️ Settings / Gear</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 384 512" fill="currentColor"><path d="M64 464c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16H224v80c0 17.7 14.3 32 32 32h80V448c0 8.8-7.2 16-16 16H64zM64 0C28.7 0 0 28.7 0 64V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V154.5c0-17-6.7-33.3-18.7-45.3L274.7 18.7C262.7 6.7 246.5 0 229.5 0H64zm56 256c-13.3 0-24 10.7-24 24s10.7 24 24 24H264c13.3 0 24-10.7 24-24s-10.7-24-24-24H120zm0 96c-13.3 0-24 10.7-24 24s10.7 24 24 24H264c13.3 0 24-10.7 24-24s-10.7-24-24-24H120z"/></svg>'>📄 File / Document</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 512 512" fill="currentColor"><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>'>🔍 Search</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 512 512" fill="currentColor"><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>'>✏️ Edit / Pen</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 448 512" fill="currentColor"><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>'>🗑️ Delete / Trash</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 512 512" fill="currentColor"><path d="M288 32c0-17.7-14.3-32-32-32s-32 14.3-32 32V274.7l-73.4-73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l128 128c12.5 12.5 32.8 12.5 45.3 0l128-128c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L288 274.7V32zM64 352c-35.3 0-64 28.7-64 64v32c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V416c0-35.3-28.7-64-64-64H346.5l-45.3 45.3c-25 25-65.5 25-90.5 0L165.5 352H64zm368 56a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/></svg>'>💾 Download / Save</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 448 512" fill="currentColor"><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>'>➕ Add / Plus</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 448 512" fill="currentColor"><path d="M0 80V229.5c0 17 6.7 33.3 18.7 45.3l176 176c25 25 65.5 25 90.5 0L418.7 317.3c25-25 25-65.5 0-90.5L242.7 50.7c-12-12-28.3-18.7-45.3-18.7H48C21.5 32 0 53.5 0 80zm112 32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/></svg>'>🏷️ Tag / Label</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 384 512" fill="currentColor"><path d="M48 0C21.5 0 0 21.5 0 48V464l96 48 96-48 96 48 96-48V48c0-26.5-21.5-48-48-48H48zM64 240c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16v32c0 8.8-7.2 16-16 16H80c-8.8 0-16-7.2-16-16V240zm112-16h32c8.8 0 16 7.2 16 16v32c0 8.8-7.2 16-16 16H176c-8.8 0-16-7.2-16-16V240c0-8.8 7.2-16 16-16zm80 16c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16v32c0 8.8-7.2 16-16 16H272c-8.8 0-16-7.2-16-16V240zM80 96h32c8.8 0 16 7.2 16 16v32c0 8.8-7.2 16-16 16H80c-8.8 0-16-7.2-16-16V112c0-8.8 7.2-16 16-16zm80 16c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16v32c0 8.8-7.2 16-16 16H176c-8.8 0-16-7.2-16-16V112zm112-16h32c8.8 0 16 7.2 16 16v32c0 8.8-7.2 16-16 16H272c-8.8 0-16-7.2-16-16V112c0-8.8 7.2-16 16-16z"/></svg>'>🏢 Building / Company</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 576 512" fill="currentColor"><path d="M0 24C0 10.7 10.7 0 24 0H69.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>'>🛒 Shopping Cart</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 512 512" fill="currentColor"><path d="M64 64c0-17.7-14.3-32-32-32S0 46.3 0 64V400c0 44.2 35.8 80 80 80H480c17.7 0 32-14.3 32-32s-14.3-32-32-32H80c-8.8 0-16-7.2-16-16V64zm406.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L320 210.7l-57.4-57.4c-12.5-12.5-32.8-12.5-45.3 0l-112 112c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L240 221.3l57.4 57.4c12.5 12.5 32.8 12.5 45.3 0l128-128z"/></svg>'>📊 Chart / Analytics</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 512 512" fill="currentColor"><path d="M3.9 54.9C10.5 40.9 24.5 32 40 32H472c15.5 0 29.5 8.9 36.1 22.9s4.6 30.5-5.2 42.5L320 320.9V448c0 12.1-6.8 23.2-17.7 28.6s-23.8 4.3-33.5-3l-64-48c-8.1-6-12.8-15.5-12.8-25.6l0-122.6L9 97.3C-.7 85.4-2.8 66.8 3.9 54.9z"/></svg>'>🔽 Filter</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 512 512" fill="currentColor"><path d="M288 32c0-17.7-14.3-32-32-32s-32 14.3-32 32V274.7l-73.4-73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l128 128c12.5 12.5 32.8 12.5 45.3 0l128-128c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L288 274.7V32zM64 352c-35.3 0-64 28.7-64 64v32c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V416c0-35.3-28.7-64-64-64H346.5l-45.3 45.3c-25 25-65.5 25-90.5 0L165.5 352H64zm368 56a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/></svg>'>📥 Export / Download</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 512 512" fill="currentColor"><path d="M142.9 142.9c-12.5 12.5-12.5 32.8 0 45.3l144 144c12.5 12.5 32.8 12.5 45.3 0l144-144c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L336 234.7V32c0-17.7-14.3-32-32-32s-32 14.3-32 32V234.7L188.2 142.9c-12.5-12.5-32.8-12.5-45.3 0zM32 320c-17.7 0-32 14.3-32 32s14.3 32 32 32H480c17.7 0 32-14.3 32-32s-14.3-32-32-32H32zm0 128c-17.7 0-32 14.3-32 32s14.3 32 32 32H480c17.7 0 32-14.3 32-32s-14.3-32-32-32H32z"/></svg>'>🔄 Refresh / Sync</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 448 512" fill="currentColor"><path d="M144 144v48H304V144c0-35.3-28.7-64-64-64s-64 28.7-64 64zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H80z"/></svg>'>🔒 Lock / Security</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 448 512" fill="currentColor"><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zM337 209L209 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L303 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>'>✅ Check / Status</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 448 512" fill="currentColor"><path d="M0 80C0 53.5 21.5 32 48 32h352c26.5 0 48 21.5 48 48v352c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V80zM48 96v320h352V96H48zm64 64c0-17.7 14.3-32 32-32h192c17.7 0 32 14.3 32 32s-14.3 32-32 32H144c-17.7 0-32-14.3-32-32zm0 96c0-17.7 14.3-32 32-32h192c17.7 0 32 14.3 32 32s-14.3 32-32 32H144c-17.7 0-32-14.3-32-32zm0 96c0-17.7 14.3-32 32-32h192c17.7 0 32 14.3 32 32s-14.3 32-32 32H144c-17.7 0-32-14.3-32-32z"/></svg>'>📋 List / Menu</option>
                        <option value='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 448 512" fill="currentColor"><path d="M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"/></svg>'>☰ Menu / Bars</option>
                    </select>
                    <div id="iconPreview" class="mt-2 p-3 bg-base-200 rounded-lg hidden w-full">
                        <label class="label">
                            <span class="label-text font-semibold">Icon Preview:</span>
                        </label>
                        <div class="flex items-center gap-2" id="previewContent"></div>
                    </div>
                </div>
                
                <div class="form-control min-w-[100px]">
                    <label class="label">
                        <span class="label-text">Menu Order</span>
                    </label>
                    <input type="number" name="menu_order" id="menuOrder" class="input input-bordered w-full" value="0" min="0">
                </div>
                
                <div class="form-control min-w-[150px]">
                    <label class="label">
                        <span class="label-text">Menu Group</span>
                    </label>
                    <select name="menu_group" id="menuGroup" class="select select-bordered w-full">
                        <option value="main">Main</option>
                        <option value="master_management">Master Management</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-control">
                    <label class="label cursor-pointer">
                        <span class="label-text">Visible</span>
                        <input type="checkbox" name="is_visible" id="menuVisible" class="toggle toggle-primary" checked>
                    </label>
                </div>
                
                <div class="form-control">
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <span id="submitBtnText">Add Menu Item</span>
                        </button>
                        <button type="button" class="btn btn-ghost" id="cancelBtn" style="display: none;">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Menu Items Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 class="card-title capitalize">
                    <i class="fas fa-list"></i>
                    Menu Items
                </h2>
                <div class="flex gap-2">
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-success btn-sm sm:btn-md">
                            <i class="fas fa-file-export"></i> <span class="hidden sm:inline">Export</span>
                        </label>
                        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><a id="exportExcel"><i class="fas fa-file-excel text-success"></i> Export as Excel</a></li>
                            <li><a id="exportCSV"><i class="fas fa-file-csv text-primary"></i> Export as CSV</a></li>
                        </ul>
                    </div>
                    <button id="refreshBtn" class="btn btn-outline btn-sm sm:btn-md">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <input type="text" id="searchInput" placeholder="Search menu items..." class="input input-bordered w-64">
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role</th>
                            <th>Label</th>
                            <th>URL</th>
                            <th>Order</th>
                            <th>Group</th>
                            <th>Visible</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="menuTableBody">
                        <tr>
                            <td colspan="9" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script>
        let menus = [];
        let roles = [];
        let editingId = null;

        // Load roles
        function loadRoles() {
            fetch('../api/admin/roles.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        roles = data.data;
                        populateRoleSelects();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading roles:', error);
                    showToast('Failed to load roles', 'error');
                });
        }

        // Populate role selects
        function populateRoleSelects() {
            const roleFilter = document.getElementById('roleFilter');
            const menuRole = document.getElementById('menuRole');
            
            const roleOptions = roles.map(role => 
                `<option value="${role.role_code}">${role.role_name} (${role.role_code})</option>`
            ).join('');
            
            roleFilter.innerHTML = '<option value="">All Roles</option>' + roleOptions;
            menuRole.innerHTML = '<option value="">Select Role</option>' + roleOptions;
        }

        // Load menu items
        function loadMenus() {
            fetch('../api/admin/role-menu-settings.php?action=list_all')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        menus = data.data;
                        renderMenus();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading menus:', error);
                    showToast('Failed to load menu items', 'error');
                });
        }

        // Render menu items table
        function renderMenus() {
            const tbody = document.getElementById('menuTableBody');
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            
            let filteredMenus = menus;
            
            if (roleFilter) {
                filteredMenus = filteredMenus.filter(menu => menu.role === roleFilter);
            }
            
            if (searchTerm) {
                filteredMenus = filteredMenus.filter(menu => 
                    menu.menu_item_label.toLowerCase().includes(searchTerm) ||
                    menu.menu_item_url.toLowerCase().includes(searchTerm) ||
                    menu.role.toLowerCase().includes(searchTerm)
                );
            }

            if (filteredMenus.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center">No menu items found</td></tr>';
                return;
            }

            tbody.innerHTML = filteredMenus.map(menu => `
                <tr>
                    <td>${menu.id}</td>
                    <td><code class="badge badge-outline">${menu.role}</code></td>
                    <td><strong>${menu.menu_item_label}</strong></td>
                    <td><code class="text-xs">${menu.menu_item_url}</code></td>
                    <td>${menu.menu_order}</td>
                    <td><span class="badge badge-ghost">${menu.menu_group || 'main'}</span></td>
                    <td>
                        <span class="badge ${menu.is_visible ? 'badge-success' : 'badge-error'}">
                            ${menu.is_visible ? 'Yes' : 'No'}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${menu.is_active ? 'badge-success' : 'badge-error'}">
                            ${menu.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${menu.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-error delete-btn" data-id="${menu.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        // Edit menu item - make it global for event delegation
        window.editMenu = function(id) {
            const menu = menus.find(m => m.id == id || m.id === id);
            if (!menu) {
                showToast('Menu item not found', 'error');
                return;
            }

            editingId = id;
            document.getElementById('menuId').value = menu.id;
            document.getElementById('menuRole').value = menu.role;
            document.getElementById('menuLabel').value = menu.menu_item_label;
            document.getElementById('menuUrl').value = menu.menu_item_url;
            document.getElementById('menuIcon').value = menu.menu_item_icon || '';
            document.getElementById('menuOrder').value = menu.menu_order;
            document.getElementById('menuGroup').value = menu.menu_group || 'main';
            document.getElementById('menuVisible').checked = menu.is_visible == 1;
            
            // Update icon preview when editing
            if (menu.menu_item_icon) {
                iconPreview.classList.remove('hidden');
                previewContent.innerHTML = menu.menu_item_icon + '<span class="ml-2 text-sm">Icon preview</span>';
            } else {
                iconPreview.classList.add('hidden');
            }
            
            document.getElementById('formTitle').textContent = 'Edit Menu Item';
            document.getElementById('submitBtnText').textContent = 'Update Menu Item';
            document.getElementById('cancelBtn').style.display = 'inline-flex';
            
            document.getElementById('menuForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        // Delete menu item - make it global for event delegation
        window.deleteMenu = async function(id) {
            const confirmResult = await showConfirm(
                'Delete Menu Item?',
                'This will permanently delete this menu item.',
                'Yes, delete it!',
                'Cancel'
            );

            if (!confirmResult.isConfirmed) {
                return;
            }

            // Get CSRF token
            const csrfResponse = await fetch('../auth/get-csrf-token.php');
            const csrfData = await csrfResponse.json();
            const csrfToken = csrfData.status === 'success' ? csrfData.data.csrf_token : '';

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            formData.append('csrf_token', csrfToken);

            fetch('../api/admin/role-menu-settings.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success', 2000);
                        loadMenus();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting menu item:', error);
                    showToast('Failed to delete menu item', 'error');
                });
        };

        // Cancel edit
        document.getElementById('cancelBtn').addEventListener('click', () => {
            editingId = null;
            document.getElementById('menuForm').reset();
            document.getElementById('formTitle').textContent = 'Add New Menu Item';
            document.getElementById('submitBtnText').textContent = 'Add Menu Item';
            document.getElementById('cancelBtn').style.display = 'none';
        });

        // Form submit
        document.getElementById('menuForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Get CSRF token
            const csrfResponse = await fetch('../auth/get-csrf-token.php');
            const csrfData = await csrfResponse.json();
            const csrfToken = csrfData.status === 'success' ? csrfData.data.csrf_token : '';
            
            const formData = new FormData(e.target);
            formData.append('action', editingId ? 'update' : 'create');
            formData.append('csrf_token', csrfToken);
            if (editingId) {
                formData.append('id', editingId);
                formData.append('is_active', '1'); // Keep active when updating
            }

            fetch('../api/admin/role-menu-settings.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        document.getElementById('menuForm').reset();
                        editingId = null;
                        document.getElementById('formTitle').textContent = 'Add New Menu Item';
                        document.getElementById('submitBtnText').textContent = 'Add Menu Item';
                        document.getElementById('cancelBtn').style.display = 'none';
                        loadMenus();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error saving menu item:', error);
                    showToast('Failed to save menu item', 'error');
                });
        });

        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', function() {
            const icon = this.querySelector('i');
            icon.classList.add('fa-spin');
            loadMenus();
            setTimeout(() => {
                icon.classList.remove('fa-spin');
            }, 700);
        });

        // Export functions
        function exportToExcel() {
            if (typeof XLSX === 'undefined') {
                showToast('Excel export library not loaded. Please refresh the page.', 'error');
                return;
            }
            
            try {
                const filteredMenus = getFilteredMenus();
                if (filteredMenus.length === 0) {
                    showToast('No menu items found to export', 'warning');
                    return;
                }
                
                const headers = ['ID', 'Role', 'Label', 'URL', 'Order', 'Group', 'Visible', 'Active'];
                const rows = filteredMenus.map(menu => [
                    menu.id,
                    menu.role,
                    menu.menu_item_label,
                    menu.menu_item_url,
                    menu.menu_order,
                    menu.menu_group || 'main',
                    menu.is_visible ? 'Yes' : 'No',
                    menu.is_active ? 'Yes' : 'No'
                ]);
                
                const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "MenuItems");
                const date = new Date();
                const dateStr = date.toISOString().split('T')[0];
                XLSX.writeFile(wb, `Menu_Items_${dateStr}.xlsx`);
                
                showToast('Menu items have been exported to Excel', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }

        function exportToCSV() {
            if (typeof saveAs === 'undefined') {
                showToast('FileSaver library not loaded. Please refresh the page.', 'error');
                return;
            }
            
            try {
                const filteredMenus = getFilteredMenus();
                if (filteredMenus.length === 0) {
                    showToast('No menu items found to export', 'warning');
                    return;
                }
                
                const headers = ['ID', 'Role', 'Label', 'URL', 'Order', 'Group', 'Visible', 'Active'];
                const csvRows = filteredMenus.map(menu => {
                    const row = [
                        menu.id,
                        menu.role,
                        menu.menu_item_label.replace(/"/g, '""'),
                        menu.menu_item_url.replace(/"/g, '""'),
                        menu.menu_order,
                        (menu.menu_group || 'main').replace(/"/g, '""'),
                        menu.is_visible ? 'Yes' : 'No',
                        menu.is_active ? 'Yes' : 'No'
                    ];
                    return row.map(cell => {
                        let text = String(cell);
                        if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                            text = `"${text}"`;
                        }
                        return text;
                    }).join(',');
                });
                
                csvRows.unshift(headers.join(','));
                const csvContent = csvRows.join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const date = new Date();
                const dateStr = date.toISOString().split('T')[0];
                saveAs(blob, `Menu_Items_${dateStr}.csv`);
                
                showToast('Menu items have been exported to CSV', 'success', 1500);
            } catch (error) {
                console.error('Export error:', error);
                showToast('An error occurred while exporting: ' + error.message, 'error');
            }
        }

        function getFilteredMenus() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            
            let filteredMenus = menus;
            
            if (roleFilter) {
                filteredMenus = filteredMenus.filter(menu => menu.role === roleFilter);
            }
            
            if (searchTerm) {
                filteredMenus = filteredMenus.filter(menu => 
                    menu.menu_item_label.toLowerCase().includes(searchTerm) ||
                    menu.menu_item_url.toLowerCase().includes(searchTerm) ||
                    menu.role.toLowerCase().includes(searchTerm)
                );
            }
            
            return filteredMenus;
        }

        // Export event listeners
        document.getElementById('exportExcel').addEventListener('click', function(e) {
            e.preventDefault();
            exportToExcel();
        });

        document.getElementById('exportCSV').addEventListener('click', function(e) {
            e.preventDefault();
            exportToCSV();
        });

        // Event delegation for edit and delete buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-btn')) {
                const btn = e.target.closest('.edit-btn');
                const id = parseInt(btn.getAttribute('data-id'), 10);
                if (id) {
                    window.editMenu(id);
                }
            } else if (e.target.closest('.delete-btn')) {
                const btn = e.target.closest('.delete-btn');
                const id = parseInt(btn.getAttribute('data-id'), 10);
                if (id) {
                    window.deleteMenu(id);
                }
            }
        });

        // Icon preview functionality
        const menuIconSelect = document.getElementById('menuIcon');
        const iconPreview = document.getElementById('iconPreview');
        const previewContent = document.getElementById('previewContent');
        
        menuIconSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            if (selectedValue) {
                iconPreview.classList.remove('hidden');
                previewContent.innerHTML = selectedValue + '<span class="ml-2 text-sm">Icon preview</span>';
            } else {
                iconPreview.classList.add('hidden');
            }
        });

        // Search and filter
        document.getElementById('searchInput').addEventListener('input', renderMenus);
        document.getElementById('roleFilter').addEventListener('change', renderMenus);

        // Load on page load
        loadRoles();
        loadMenus();
    </script>
<?php include '../common/layout-footer.php'; ?>

