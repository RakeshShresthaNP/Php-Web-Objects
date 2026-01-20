<style scoped>
.wrapper{
	@apply fixed top-0 left-0 right-0 z-50;
}

.header-bar {
	@apply text-gray-200 bg-primary flex items-center justify-between;
	@apply px-3 py-3 sm:px-4 sm:py-4 lg:p-5;
	min-height: 56px;
}

@media (min-width: 640px) {
	.header-bar {
		min-height: 64px;
	}
}
</style>

<template>
	<main class="wrapper">
		<section class="header-bar">
			<slot name="start"></slot>
			<slot name="end"></slot>
			<slot name="bottom"></slot>
		</section>
	</main>
</template>
