<template>
	<h3><u>Personen:</u></h3>
	<div v-for="(person, index) in people" :key="index">
		<p v-if="person">
			{{
				person.displayname
					? person.displayname
					: person.forename + " " + person.surname
			}}
			<span v-if="person.email">(<a :href="'mailto:abc' + person.email">{{ person.email }}</a>)</span>
		</p>
	</div>
	<br />
</template>

<script>
export default {
	props: {
		people: {
			type: Object,
			required: true,
		},
	},
	setup() {
		return {};
	},
};
</script>

<style lang="scss" scoped>
p {
	margin: 0;
	margin: 0.2rem 0 0.2rem 0;
}
</style>