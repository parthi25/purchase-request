<dialog id="create_modal" class="modal modal-middle">
        <div class="modal-box max-w-5xl w-full max-h-[90vh] p-0 flex flex-col overflow-hidden">

            <!-- Modal Header -->
            <div class="bg-base-200 p-4 border-b border-base-300 flex-shrink-0">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-lg">Create PR</h3>
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost">✕</button>
                    </form>
                </div>
            </div>

            <!-- Modal Body - Scrollable -->
            <div class="p-4 sm:p-6 overflow-y-auto flex-1 min-h-0">
                <?php include 'create-pr-form.php'; ?>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>