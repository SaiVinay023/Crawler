<div class="bg-slate-50 min-h-screen py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-center mb-10 border-b border-slate-200 pb-6">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">
                    Product <span class="text-indigo-600">Catalog</span>
                </h1>
                <p class="mt-2 text-sm text-slate-500 font-medium italic">Showing {{ $products->total() }} premium items</p>
            </div>
            
            <div class="flex items-center space-x-3 mt-4 md:mt-0 bg-white p-2 rounded-xl shadow-sm border border-slate-100">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-2">Sort by:</span>
                <button wire:click="sortBy('price')" 
                    class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 {{ $sortField === 'price' ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                    Price {!! $sortField === 'price' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' !!}
                </button>
                <button wire:click="sortBy('created_at')" 
                    class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 {{ $sortField === 'created_at' ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                    Date {!! $sortField === 'created_at' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' !!}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            @foreach($products as $product)
                <div class="group relative bg-white border border-slate-200 rounded-2xl flex flex-col overflow-hidden hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                    <div class="aspect-w-4 aspect-h-3 bg-slate-100 group-hover:opacity-90 sm:h-56 relative">
                        @php
                            $displayImage = $product->image_url;
                            if ($displayImage && !str_starts_with($displayImage, 'http')) {
                                $displayImage = 'https://sandbox.oxylabs.io' . (str_starts_with($displayImage, '/') ? '' : '/') . $displayImage;
                            }
                        @endphp
                        <img src="{{ $displayImage ?? 'https://via.placeholder.com/400x300?text=No+Image' }}" 
                             alt="{{ $product->name }}" 
                             class="w-full h-full object-center object-contain p-4 transition-transform duration-500 group-hover:scale-105">
                        
                        <div class="absolute bottom-2 right-2 bg-white/90 backdrop-blur px-3 py-1 rounded-lg shadow-sm">
                             <p class="text-lg font-black text-indigo-600">
                                €{{ number_format($product->price, 2) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex-1 p-6 flex flex-col">
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="px-2 py-1 bg-indigo-50 text-[10px] font-bold tracking-widest uppercase text-indigo-600 rounded-md">
                                    {{ $product->category ?? 'General' }}
                                </span>
                            </div>
                            <h3 class="mt-3 text-lg font-bold text-slate-900 leading-tight group-hover:text-indigo-600 transition-colors">
                                {{ $product->name }}
                            </h3>
                            <p class="mt-2 text-sm text-slate-500 line-clamp-3 leading-relaxed">
                                {{ $product->attributes['description'] ?? 'No description available.' }}
                            </p>
                        </div>
                        
                        <div class="mt-6 flex items-center justify-between pt-4 border-t border-slate-50">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-tighter">
                                Added: {{ $product->created_at->diffForHumans() }}
                            </span>
                            <a href="{{ $product->source_url }}" target="_blank" 
                               class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-900 text-white hover:bg-indigo-600 transition-all shadow-lg hover:shadow-indigo-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-16 flex justify-center">
            <div class="bg-white px-4 py-2 rounded-2xl shadow-sm border border-slate-200">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>