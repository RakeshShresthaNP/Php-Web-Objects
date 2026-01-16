//Content for Deposit view component
//

interface TrashCategory {
  name: string
  id: number
  thumbnail: string
  point: number
  description: string
}

const trashCategories: TrashCategory[] = [
  {
    name: 'cardboard',
    id: 1,
    thumbnail: '/kardus.png',
    point: 23,
    description: 'Rp.11.000,00,- /Kg'
  },
  {
    name: 'can',
    id: 2,
    thumbnail: '/kaleng.png',
    point: 20,
    description: 'Rp.10.500,00,- /Kg'
  },
  {
    name: 'bottle',
    id: 3,
    thumbnail: '/botol.png',
    point: 50,
    description: 'Rp.12.000,00,- /Kg'
  },
  {
    name: 'plastic',
    id: 4,
    thumbnail: '/plastik.png',
    point: 15,
    description: 'Rp.4.500,00,- /Kg'
  }
]

export default trashCategories

