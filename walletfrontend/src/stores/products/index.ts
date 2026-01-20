import { defineStore } from 'pinia'

interface Product {
  id: number
  name: string
  price: number
  image: string
  category: string
  description: string
  stock: number
  rating: number
  reviews: number
}

interface CartItem {
  productId: number
  quantity: number
}

interface ProductsState {
  products: Product[]
  searchQuery: string
  selectedCategory: string
  sortBy: string
  cart: CartItem[]
}

export const useProducts = defineStore('products', {
  state: (): ProductsState => ({
    products: [
      {
        id: 1,
        name: 'Tas Plastik Ramah Lingkungan',
        price: 75000,
        image: '/product.jpg',
        category: 'aksesoris',
        description: 'Tas plastik daur ulang yang ramah lingkungan',
        stock: 50,
        rating: 4.5,
        reviews: 120
      },
      {
        id: 2,
        name: 'Botol Minum Stainless',
        price: 85000,
        image: '/product.jpg',
        category: 'aksesoris',
        description: 'Botol minum stainless steel berkualitas tinggi',
        stock: 30,
        rating: 4.8,
        reviews: 95
      },
      {
        id: 3,
        name: 'Kotak Makan Bento',
        price: 65000,
        image: '/product.jpg',
        category: 'aksesoris',
        description: 'Kotak makan bento dengan desain modern',
        stock: 40,
        rating: 4.3,
        reviews: 78
      },
      {
        id: 4,
        name: 'Tumbler Eco Friendly',
        price: 95000,
        image: '/product.jpg',
        category: 'aksesoris',
        description: 'Tumbler ramah lingkungan dengan isolasi panas',
        stock: 25,
        rating: 4.7,
        reviews: 150
      },
      {
        id: 5,
        name: 'Sedotan Stainless',
        price: 25000,
        image: '/product.jpg',
        category: 'aksesoris',
        description: 'Set sedotan stainless dengan sikat pembersih',
        stock: 100,
        rating: 4.6,
        reviews: 200
      },
      {
        id: 6,
        name: 'Kantong Belanja Kain',
        price: 45000,
        image: '/product.jpg',
        category: 'aksesoris',
        description: 'Kantong belanja dari kain daur ulang',
        stock: 60,
        rating: 4.4,
        reviews: 110
      }
    ],
    searchQuery: '',
    selectedCategory: 'all',
    sortBy: 'default',
    cart: []
  }),

  getters: {
    // Filtered and sorted products
    filteredProducts: (state): Product[] => {
      let filtered = [...state.products]

      // Filter by search query
      if (state.searchQuery) {
        const query = state.searchQuery.toLowerCase()
        filtered = filtered.filter(product =>
          product.name.toLowerCase().includes(query) ||
          product.description.toLowerCase().includes(query)
        )
      }

      // Filter by category
      if (state.selectedCategory !== 'all') {
        filtered = filtered.filter(product =>
          product.category === state.selectedCategory
        )
      }

      // Sort products
      switch (state.sortBy) {
        case 'price-low':
          filtered.sort((a, b) => a.price - b.price)
          break
        case 'price-high':
          filtered.sort((a, b) => b.price - a.price)
          break
        case 'rating':
          filtered.sort((a, b) => b.rating - a.rating)
          break
        case 'name':
          filtered.sort((a, b) => a.name.localeCompare(b.name))
          break
        default:
          // Keep original order
          break
      }

      return filtered
    },

    // Get unique categories
    categories: (state): string[] => {
      const cats = new Set(state.products.map(p => p.category))
      return ['all', ...Array.from(cats)]
    },

    // Cart total
    cartTotal: (state): number => {
      return state.cart.reduce((total, item) => {
        const product = state.products.find(p => p.id === item.productId)
        return total + (product ? product.price * item.quantity : 0)
      }, 0)
    },

    // Cart item count
    cartItemCount: (state): number => {
      return state.cart.reduce((total, item) => total + item.quantity, 0)
    }
  },

  actions: {
    // Set search query
    setSearchQuery(query: string): void {
      this.searchQuery = query
    },

    // Set selected category
    setCategory(category: string): void {
      this.selectedCategory = category
    },

    // Set sort option
    setSortBy(sortBy: string): void {
      this.sortBy = sortBy
    },

    // Add to cart
    addToCart(productId: number, quantity: number = 1): void {
      const existingItem = this.cart.find(item => item.productId === productId)
      
      if (existingItem) {
        existingItem.quantity += quantity
      } else {
        this.cart.push({ productId, quantity })
      }
    },

    // Remove from cart
    removeFromCart(productId: number): void {
      const index = this.cart.findIndex(item => item.productId === productId)
      if (index > -1) {
        this.cart.splice(index, 1)
      }
    },

    // Update cart quantity
    updateCartQuantity(productId: number, quantity: number): void {
      const item = this.cart.find(item => item.productId === productId)
      if (item) {
        if (quantity <= 0) {
          this.removeFromCart(productId)
        } else {
          item.quantity = quantity
        }
      }
    },

    // Clear cart
    clearCart(): void {
      this.cart = []
    },

    // Get product by ID
    getProductById(id: number): Product | undefined {
      return this.products.find(p => p.id === id)
    }
  }
})

