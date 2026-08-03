<template>
  <div class="space-y-5">
    <div>
      <h1 class="text-2xl font-bold text-gray-800">Student Ranking</h1>
      <p class="text-gray-500 text-sm mt-1">Top performers ranked by average score</p>
    </div>

    <div v-if="loading" class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
      <svg class="animate-spin w-8 h-8 text-indigo-500 mx-auto" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
      </svg>
    </div>

    <div v-else class="space-y-4">
      <!-- Top 3 podium -->
      <div v-if="ranking.length >= 3"
        class="grid grid-cols-3 gap-4">
        <!-- 2nd place -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center flex flex-col items-center justify-end mt-8">
          <div class="w-14 h-14 rounded-full bg-gray-200 text-gray-600 font-black text-2xl flex items-center justify-center mb-2">
            {{ initials(ranking[1]?.name) }}
          </div>
          <p class="font-semibold text-gray-800 text-sm">{{ ranking[1]?.name }}</p>
          <p class="text-2xl font-black text-gray-500 mt-1">{{ ranking[1]?.avg_score }}%</p>
          <div class="w-full h-16 bg-gray-300 rounded-t-xl mt-3 flex items-end justify-center pb-2">
            <span class="text-white font-black text-2xl">2</span>
          </div>
        </div>
        <!-- 1st place -->
        <div class="bg-gradient-to-b from-yellow-400 to-yellow-500 rounded-2xl shadow-lg p-5 text-center flex flex-col items-center">
          <span class="text-3xl mb-2">🏆</span>
          <div class="w-16 h-16 rounded-full bg-white text-yellow-600 font-black text-2xl flex items-center justify-center mb-2 shadow">
            {{ initials(ranking[0]?.name) }}
          </div>
          <p class="font-bold text-white text-sm">{{ ranking[0]?.name }}</p>
          <p class="text-3xl font-black text-white mt-1">{{ ranking[0]?.avg_score }}%</p>
          <div class="w-full h-24 bg-yellow-600 rounded-t-xl mt-3 flex items-end justify-center pb-2">
            <span class="text-white font-black text-3xl">1</span>
          </div>
        </div>
        <!-- 3rd place -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center flex flex-col items-center justify-end mt-14">
          <div class="w-14 h-14 rounded-full bg-orange-100 text-orange-600 font-black text-2xl flex items-center justify-center mb-2">
            {{ initials(ranking[2]?.name) }}
          </div>
          <p class="font-semibold text-gray-800 text-sm">{{ ranking[2]?.name }}</p>
          <p class="text-2xl font-black text-orange-500 mt-1">{{ ranking[2]?.avg_score }}%</p>
          <div class="w-full h-10 bg-orange-300 rounded-t-xl mt-3 flex items-end justify-center pb-1">
            <span class="text-white font-black text-xl">3</span>
          </div>
        </div>
      </div>

      <!-- Full leaderboard table -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-800">Full Leaderboard</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Rank</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Student</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Avg Score</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Exams Taken</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Exams Passed</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pass Rate</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-if="!ranking.length">
                <td colspan="6" class="px-4 py-8 text-center text-gray-400">No ranking data yet.</td>
              </tr>
              <tr v-for="r in ranking" :key="r.rank"
                :class="r.rank <= 3 ? 'bg-yellow-50/50' : 'hover:bg-gray-50'"
                class="transition-colors">
                <td class="px-4 py-3">
                  <span :class="[
                      'w-8 h-8 rounded-full flex items-center justify-center font-black text-sm',
                      r.rank===1 ? 'bg-yellow-400 text-white' :
                      r.rank===2 ? 'bg-gray-300 text-gray-700' :
                      r.rank===3 ? 'bg-orange-300 text-white' :
                      'bg-gray-100 text-gray-600'
                    ]">
                    {{ r.rank <= 3 ? ['🥇','🥈','🥉'][r.rank-1] : r.rank }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold text-xs
                                flex items-center justify-center flex-shrink-0">
                      {{ initials(r.name) }}
                    </div>
                    <div>
                      <p class="font-medium text-gray-800">{{ r.name }}</p>
                      <p class="text-xs text-gray-400">{{ r.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <div class="w-20 bg-gray-200 rounded-full h-2">
                      <div class="bg-indigo-500 h-2 rounded-full"
                        :style="{ width: Math.min(r.avg_score, 100) + '%' }"/>
                    </div>
                    <span class="font-bold text-indigo-600">{{ r.avg_score }}%</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-gray-600 font-medium">{{ r.exams_taken }}</td>
                <td class="px-4 py-3 text-green-600 font-medium">{{ r.exams_passed }}</td>
                <td class="px-4 py-3">
                  <span class="font-semibold"
                    :class="(r.exams_passed/Math.max(r.exams_taken,1)*100) >= 50
                      ? 'text-green-600' : 'text-red-500'">
                    {{ r.exams_taken ? Math.round(r.exams_passed/r.exams_taken*100) : 0 }}%
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/axios'

const ranking = ref([])
const loading = ref(true)

function initials(name) {
  return (name || 'U').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
}

onMounted(async () => {
  try {
    const { data } = await api.get('/analytics/ranking', { params: { limit: 50 } })
    ranking.value  = data
  } finally { loading.value = false }
})
</script>
