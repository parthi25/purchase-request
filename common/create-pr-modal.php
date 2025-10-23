<dialog id="create_modal" class="modal modal-middle">
        <div class="modal-box max-w-5xl p-0 overflow-hidden">

            <!-- Modal Header -->
            <div class="bg-base-200 p-4 border-b border-base-300">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-lg">Create PR</h3>
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost">✕</button>
                    </form>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <?php include 'create-pr-form.php'; ?>
            </div>
        </div>
    </dialog>