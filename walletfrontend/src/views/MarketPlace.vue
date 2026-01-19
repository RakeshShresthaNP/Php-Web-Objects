<template>
    <main class="pt-20"> 
        <HeaderMarket />
        
        <!-- Search and Filter Section -->
        <section class="mt-6 mb-6 w-full max-w-md md:max-w-lg mx-auto">
            <!-- Search Bar -->
            <div class="relative mb-4">
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="$t('marketplace.searchPlaceholder')"
                    class="w-full bg-secondary text-gray-300 rounded-lg px-4 py-3 pl-10 focus:ring-2 focus:ring-green-500 focus:outline-none"
                />
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>

            <!-- Filter and Sort -->
            <div class="flex flex-col sm:flex-row gap-3 mb-4">
                <!-- Category Filter -->
                <select
                    v-model="selectedCategory"
                    class="flex-1 bg-secondary text-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:outline-none"
                >
                    <option value="all">{{ $t('marketplace.allCategories') }}</option>
                    <option value="aksesoris">{{ $t('marketplace.accessories') }}</option>
                    <option value="makanan">{{ $t('marketplace.food') }}</option>
                    <option value="minuman">{{ $t('marketplace.drink') }}</option>
                </select>

                <!-- Sort -->
                <select
                    v-model="sortBy"
                    class="flex-1 bg-secondary text-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:outline-none"
                >
                    <option value="default">{{ $t('marketplace.sortDefault') }}</option>
                    <option value="price-low">{{ $t('marketplace.sortPriceLow') }}</option>
                    <option value="price-high">{{ $t('marketplace.sortPriceHigh') }}</option>
                    <option value="rating">{{ $t('marketplace.sortRating') }}</option>
                    <option value="name">{{ $t('marketplace.sortName') }}</option>
                </select>
            </div>

            <!-- Results Count -->
            <p class="text-sm text-gray-400 mb-4">
                {{ $t('marketplace.showingProducts', { count: filteredProducts.length }) }}
            </p>
        </section>

        <!-- Products Grid -->
        <section class="flex flex-wrap justify-between gap-4">
            <template v-if="filteredProducts.length > 0">
                <div
                    v-for="product in filteredProducts"
                    :key="product.id"
                    class="w-full sm:w-5/12 mb-6 sm:mb-10 cursor-pointer hover:opacity-80 transition-opacity"
                    @click="viewProduct(product)"
                >
                    <img :src="product.image" :alt="product.name" class="w-full mb-2 rounded-lg" />
                    <div class="text-sm flex items-center justify-between">
                        <span class="text-gray-300">
                            <h1 class="font-medium">{{ product.name }}</h1>
                            <p class="text-gray-400">{{ formatCurrency(product.price) }}</p>
                            <div class="flex items-center gap-1 mt-1">
                                <i class="fas fa-star text-yellow-400 text-xs"></i>
                                <span class="text-xs text-gray-500">{{ product.rating }}</span>
                                <span class="text-xs text-gray-500">({{ product.reviews }})</span>
                            </div>
                        </span>
                        <i class="fa fa-arrow-right text-lg text-green-600"></i>
                    </div>
                </div>
            </template>
            <div v-else class="w-full text-center py-10">
                <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-400">{{ $t('marketplace.noProductsFound') }}</p>
            </div>
        </section>
    </main>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import HeaderMarket from '@/components/Header.vue'
import { useProducts } from '@/stores/products'
import { formatCurrency } from '@/utils/dateFormatter'
import { useToastNotification } from '@/composables/useToast'

const { t } = useI18n()
const router = useRouter()
const productsStore = useProducts()
const toast = useToastNotification()

const searchQuery = computed({
  get: () => productsStore.searchQuery,
  set: (value) => productsStore.setSearchQuery(value)
})

const selectedCategory = computed({
  get: () => productsStore.selectedCategory,
  set: (value) => productsStore.setCategory(value)
})

const sortBy = computed({
  get: () => productsStore.sortBy,
  set: (value) => productsStore.setSortBy(value)
})

const filteredProducts = computed(() => productsStore.filteredProducts)

const viewProduct = (product) => {
  // Navigate to product detail or add to cart
  toast.info(t('marketplace.viewingDetail', { name: product.name }))
  // router.push({ name: 'ProductDetail', params: { id: product.id } })
}

</script>
