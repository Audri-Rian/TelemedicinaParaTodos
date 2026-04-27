<script setup lang="ts">
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';
</script>

<template>
    <div class="space-y-6">
        <div class="space-y-5">
            <div class="flex items-center justify-between gap-4">
                <div class="space-y-2">
                    <Skeleton class="h-9 w-72" />
                    <Skeleton class="h-4 w-[28rem] max-w-[80vw]" />
                </div>

                <div class="flex items-center gap-2">
                    <Skeleton class="h-10 w-10 rounded-xl" />
                    <Skeleton class="h-10 w-28 rounded-xl" />
                    <Skeleton class="h-10 w-32 rounded-xl" />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Skeleton class="h-4 w-14" />
                <Skeleton class="h-8 w-16 rounded-full" />
                <Skeleton class="h-8 w-20 rounded-full" />
                <Skeleton class="h-8 w-24 rounded-full" />
                <Skeleton class="h-8 w-36 rounded-full" />
                <Skeleton class="ml-4 h-4 w-12" />
                <Skeleton class="h-8 w-24 rounded-full" />
                <Skeleton class="h-8 w-28 rounded-full" />
                <Skeleton class="h-8 w-28 rounded-full" />
                <Skeleton class="h-8 w-24 rounded-full" />
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
            <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="space-y-3 rounded-2xl border border-zinc-100 bg-zinc-50/70 p-3">
                    <div class="flex items-center justify-between">
                        <Skeleton class="h-5 w-40" />
                        <Skeleton class="h-4 w-24" />
                    </div>

                    <div class="space-y-2">
                        <div v-for="item in 5" :key="item" class="flex gap-3">
                            <div class="flex w-14 flex-col items-end pt-3">
                                <Skeleton class="h-4 w-10" />
                            </div>
                            <div class="relative flex w-5 justify-center pt-1.5">
                                <span class="h-full w-px bg-zinc-200" />
                                <Skeleton class="absolute top-2 h-2.5 w-2.5 rounded-full" />
                            </div>
                            <div class="flex flex-1 items-center justify-between rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <Skeleton class="size-10 rounded-xl" />
                                    <div class="space-y-2">
                                        <Skeleton class="h-4 w-48" />
                                        <Skeleton class="h-4 w-64 max-w-[55vw]" />
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Skeleton class="h-6 w-24 rounded-full" />
                                    <Skeleton class="h-8 w-8 rounded-lg" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="space-y-4">
                <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                    <Skeleton class="h-4 w-28" />
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <Skeleton class="h-20 rounded-xl" />
                        <Skeleton class="h-20 rounded-xl" />
                        <Skeleton class="h-20 rounded-xl" />
                        <Skeleton class="h-20 rounded-xl" />
                    </div>
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                    <Skeleton class="h-4 w-20" />
                    <div class="mt-2 space-y-3">
                        <Skeleton class="h-10 w-full rounded-lg" />
                        <Skeleton class="h-10 w-full rounded-lg" />
                        <Skeleton class="h-10 w-full rounded-lg" />
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>
